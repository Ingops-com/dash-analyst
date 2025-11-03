<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Program;
use App\Models\Annex;
use App\Models\CompanyAnnexSubmission;
use App\Models\CompanyPoeRecord;
use App\Models\Poe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Company;
use App\Services\DocumentHeaderService;

class ProgramController extends Controller
{
    private function saveAnnexFile($file, $companyId, $programId, $annexId)
    {
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Generar un nombre único para el archivo
        $storagePath = "anexos/company_{$companyId}/program_{$programId}";
        $uniqueName = uniqid() . '_' . $fileName;
        
        // Guardar el archivo
        $filePath = $file->storeAs("public/{$storagePath}", $uniqueName);
        
        // Crear el registro en la base de datos
        return CompanyAnnexSubmission::create([
            'company_id' => $companyId,
            'program_id' => $programId,
            'annex_id' => $annexId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'status' => 'Pendiente',
            // Use Auth facade for better static analysis compatibility
            'submitted_by' => Auth::id(),
        ]);
    }

    public function generatePdf(Request $request, $id)
    {
        $program = Program::findOrFail($id);
        
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'company_name' => 'required|string',
            'company_nit' => 'required|string',
            'company_address' => 'required|string',
            'company_activities' => 'required|string',
            'company_representative' => 'required|string',
            'anexos.*.id' => 'required|exists:annexes,id',
            'anexos.*.archivo' => 'required|file|max:10240'
        ]);

        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $finalDocxPath = $tempDir . '/' . uniqid('final_document_', true) . '.docx';
        $tempImagePaths = [];

        try {
            if (!extension_loaded('zip') || !extension_loaded('gd')) {
                throw new \Exception('Las extensiones Zip y GD de PHP son necesarias y no están activadas.');
            }

            // Obtener datos completos de la empresa desde la BD
            $company = Company::findOrFail($validated['company_id']);
            
            $companyData = [
                'nombre' => $company->nombre,
                'nit_empresa' => $company->nit_empresa,
                'direccion' => $company->direccion,
                'actividades' => $company->actividades,
                'representante_legal' => $company->representante_legal,
                'encargado_sgc' => $company->encargado_sgc,
                'revisado_por' => $company->revisado_por,
                'aprobado_por' => $company->aprobado_por,
                'version' => $company->version,
                'fecha_inicio' => $company->fecha_inicio ? $company->fecha_inicio->format('Y-m-d') : null,
                'logo_izquierdo' => $company->logo_izquierdo,
                'logo_derecho' => $company->logo_derecho,
                'logo_pie_de_pagina' => $company->logo_pie_de_pagina,
            ];
            
            $programData = [
                'nombre' => $program->nombre,
                'codigo' => $program->codigo,
                'version' => $program->version,
            ];

            // Determinar la plantilla basada en el tipo de programa
            if (isset($program->template_type) && $program->template_type === 'plan_saneamiento') {
                $templatePath = storage_path('plantillas/planDeSaneamientoBasico/Plantilla.docx');
            } elseif ($program->id === 1) {
                $templatePath = storage_path('plantillas/planDeSaneamientoBasico/Plantilla.docx');
            } else {
                throw new \Exception('Tipo de programa no soportado para generación de documento');
            }

            if (!file_exists($templatePath)) {
                throw new \Exception("La plantilla para este tipo de programa no se encontró en: {$templatePath}");
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // Preparar POES (fechas) para reemplazo
            $poeText = '';
            try {
                $poeIds = Poe::where('program_id', $program->id)->pluck('id')->toArray();
                if (!empty($validated['company_id']) && count($poeIds)) {
                    $poeRecords = CompanyPoeRecord::where('company_id', $validated['company_id'])
                        ->whereIn('poe_id', $poeIds)
                        ->get();
                    $poeDates = [];
                    foreach ($poeRecords as $r) {
                        if ($r->fecha_ejecucion) $poeDates[] = $r->fecha_ejecucion->format('Y-m-d');
                    }
                    $poeText = implode(', ', $poeDates);
                }
            } catch (\Throwable $ex) {
                $poeText = '';
            }

            // Variables que deben coincidir exactamente con los placeholders en la plantilla
            $commonVariables = [
                'Nombre de la empresa' => $validated['company_name'] ?? '',
                'NIT' => $validated['company_nit'] ?? '',
                'Dirección' => $validated['company_address'] ?? '',
                'actividades de la empresa' => $validated['company_activities'] ?? '',
                'Actividades de la empresa' => $validated['company_activities'] ?? '',
                'Programa' => $program->nombre ?? '',
                'Poes' => $poeText,
                'Anexo 6' => '(Anexo no proporcionado)',
            ];

            // Reemplazar variables comunes
            foreach ($commonVariables as $key => $value) {
                $templateProcessor->setValue($key, $value ?? '');
            }

            // ====== SOPORTE DE PLACEHOLDERS EN HEADER/FOOTER (sin segunda pasada) ======
            // Si la plantilla incluye placeholders en el header, los rellenamos aquí para evitar el problema de WMF al reabrir el DOCX.
            // Recomendado agregar estos placeholders en el header de la plantilla (se pueden usar algunos o todos):
            //  - ${HEADER_LOGO_LEFT}, ${HEADER_LOGO_RIGHT}, ${FOOTER_LOGO}
            //  - ${HEADER_TITLE}, ${HEADER_CODE}
            //  - ${HEADER_REVIEWED}, ${HEADER_ADDRESS}, ${HEADER_APPROVED}, ${HEADER_VERSION_DATE}

            // Helper para resolver paths de imágenes de logos
            $resolveLogoPath = function (?string $storagePath) {
                if (!$storagePath) return null;
                try {
                    if (Storage::disk('public')->exists($storagePath)) {
                        return Storage::disk('public')->path($storagePath);
                    }
                } catch (\Throwable $e) { /* ignore */ }
                $fallback = storage_path('app/public/' . ltrim((string)$storagePath, '/'));
                return file_exists($fallback) ? $fallback : null;
            };

            $leftLogoPath = $resolveLogoPath($companyData['logo_izquierdo'] ?? null);
            $rightLogoPath = $resolveLogoPath($companyData['logo_derecho'] ?? null);
            $footerLogoPath = $resolveLogoPath($companyData['logo_pie_de_pagina'] ?? null);

            // Imágenes en header/footer (si existen placeholders)
            if ($leftLogoPath) {
                foreach (['HEADER_LOGO_LEFT','LOGO_IZQ','LogoIzquierdo'] as $ph) {
                    $templateProcessor->setImageValue($ph, [ 'path' => $leftLogoPath, 'width' => 140, 'ratio' => true ]);
                }
            }
            if ($rightLogoPath) {
                foreach (['HEADER_LOGO_RIGHT','LOGO_DER','LogoDerecho'] as $ph) {
                    $templateProcessor->setImageValue($ph, [ 'path' => $rightLogoPath, 'width' => 120, 'ratio' => true ]);
                }
            }
            if ($footerLogoPath) {
                foreach (['FOOTER_LOGO','LOGO_PIE','LogoPie'] as $ph) {
                    $templateProcessor->setImageValue($ph, [ 'path' => $footerLogoPath, 'width' => 110, 'ratio' => true ]);
                }
            }

            // Textos del header
            $fechaFmt = $companyData['fecha_inicio'] ? date('M-y', strtotime($companyData['fecha_inicio'])) : '';
            $headerVars = [
                'HEADER_TITLE' => $programData['nombre'] ?? '',
                'HEADER_CODE' => $programData['codigo'] ?? '',
                'HEADER_REVIEWED' => 'Revisado por: ' . ( $companyData['revisado_por'] ?? $companyData['encargado_sgc'] ?? '' ),
                'HEADER_ADDRESS' => 'Dirección del establecimiento ' . ( $companyData['direccion'] ?? '' ),
                'HEADER_APPROVED' => 'Aprobado por: ' . ( $companyData['aprobado_por'] ?? $companyData['representante_legal'] ?? '' ),
                'HEADER_VERSION_DATE' => 'Versión ' . ($companyData['version'] ?? '') . ( $fechaFmt ? '      ' . $fechaFmt : '' ),
            ];
            foreach ($headerVars as $k => $v) { $templateProcessor->setValue($k, $v); }

            // Reemplazar placeholders de IMÁGENES
            $annexMapping = [
                'Certificado de Fumigación' => 'Anexo 1',
                'Factura de Insumos' => 'Anexo 2',
                'Registro Fotográfico' => 'Anexo 3',
                'Checklist Interno' => 'Anexo 4',
                'Memorando Aprobación' => 'Anexo 5',
            ];

            // Procesar y guardar los anexos
            $annexSubmissions = [];
            if ($request->has('anexos')) {
                foreach ($request->anexos as $index => $anexoData) {
                    if (isset($anexoData['archivo']) && $anexoData['archivo']->isValid()) {
                        $submission = $this->saveAnnexFile(
                            $anexoData['archivo'],
                            $validated['company_id'],
                            $program->id,
                            $anexoData['id']
                        );
                        $annexSubmissions[] = $submission;

                        if (str_starts_with($submission->mime_type, 'image/')) {
                            try {
                                $imagePath = Storage::path($submission->file_path);
                            } catch (\Throwable $ex) {
                                $imagePath = storage_path('app/' . ltrim($submission->file_path, '/'));
                            }
                            $tempImagePaths[] = $imagePath;

                            $annexInfo = Annex::find($anexoData['id']);
                            $placeholder = $annexInfo && isset($annexMapping[$annexInfo->nombre]) ? $annexMapping[$annexInfo->nombre] : null;
                            if ($placeholder) {
                                $templateProcessor->setImageValue($placeholder, [
                                    'path' => $imagePath,
                                    'width' => 500,
                                    'ratio' => true
                                ]);
                            }
                        }
                    }
                }
            }

            foreach ($annexMapping as $placeholder) {
                $templateProcessor->setValue($placeholder, '(Anexo no proporcionado)');
            }

            // Guardar el documento procesado temporalmente
            $templateProcessor->saveAs($finalDocxPath);

            // ===== AGREGAR HEADER PERSONALIZADO =====
            // En algunos casos las plantillas incluyen imágenes WMF/EMF que el lector de PhpWord no soporta.
            // Si ocurre, hacemos un fallback: entregamos el documento sin la inyección de header
            // y avisamos en el log para que se reemplacen los WMF por PNG/JPG en la plantilla.
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($finalDocxPath);
                $headerService = new DocumentHeaderService();

                foreach ($phpWord->getSections() as $section) {
                    $headerService->createCustomHeader($section, $companyData, $programData);
                    $headerService->addFooterLogo($section, $companyData['logo_pie_de_pagina']);
                }

                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($finalDocxPath);
            } catch (\Throwable $tex) {
                Log::warning('Header injection skipped due to template image format issue: ' . $tex->getMessage());
                // Nota: Para habilitar el header, reemplace imágenes WMF/EMF de la plantilla por PNG/JPG.
            }

            return response()->download($finalDocxPath, 'Plan-Generado.docx')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error Final: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Error interno al generar el documento: ' . $e->getMessage()], 500);
        } finally {
            foreach ($tempImagePaths as $path) {
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }
    }

    /**
     * Mostrar vista del programa con anexos y poes.
     * Opcionalmente acepta ?company_id= para incluir envíos aprobados y registros de POE de la empresa.
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->query('company_id');

        $program = Program::findOrFail($id);

        // Obtener anexos vinculados al programa
        $annexIds = DB::table('program_annexes')->where('program_id', $program->id)->pluck('annex_id')->toArray();
        $annexes = Annex::whereIn('id', $annexIds)->get()->map(function ($a) use ($companyId, $program) {
            $files = [];
            if ($companyId) {
                $subs = CompanyAnnexSubmission::where('company_id', $companyId)
                    ->where('program_id', $program->id)
                    ->where('annex_id', $a->id)
                    ->where('status', 'Aprobado')
                    ->get();

                foreach ($subs as $s) {
                    $files[] = [
                        'name' => $s->file_name,
                        'url' => asset($s->file_path),
                        'mime' => $s->mime_type,
                    ];
                }
            }

            // Mapear tipo a la nomenclatura del frontend
            // Para el programa 1 forzamos que todos los anexos sean imágenes (modo prueba)
            if ($program->id === 1) {
                $type = 'IMAGES';
            } else {
                $type = match ($a->tipo) {
                    'ISO 22000' => 'PDF',
                    'PSB' => 'XLSX',
                    'Invima' => 'FORMATO',
                    default => 'PDF'
                };
            }

            return [
                'id' => $a->id,
                'name' => $a->nombre,
                'type' => $type,
                'files' => $files,
            ];
        })->toArray();

        // POEs: si se suministra company_id, obtener registros de company_poe_records para las poes del programa
        $poes = [];
        $poeIds = Poe::where('program_id', $program->id)->pluck('id')->toArray();
        if ($companyId && count($poeIds)) {
            $records = CompanyPoeRecord::where('company_id', $companyId)
                ->whereIn('poe_id', $poeIds)
                ->get();

            foreach ($records as $r) {
                $poes[] = [
                    'id' => $r->id,
                    'date' => $r->fecha_ejecucion ? $r->fecha_ejecucion->format('Y-m-d') : null,
                ];
            }
        }

        $payload = [
            'id' => $program->id,
            'name' => $program->nombre,
            'annexes' => $annexes,
            'poes' => $poes,
        ];

        $companyPayload = null;
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $companyPayload = [
                    'id' => $company->id,
                    'name' => $company->nombre,
                    'nit' => $company->nit_empresa,
                    'address' => $company->direccion,
                    'activities' => $company->actividades,
                    'representative' => $company->representante_legal,
                ];
            }
        }

        return Inertia::render('ProgramView', [
            'program' => $payload,
            'company' => $companyPayload,
        ]);
    }
}