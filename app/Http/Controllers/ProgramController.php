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
        
        // Guardar el archivo en el disco 'public' (storage/app/public)
        $filePath = $file->storeAs($storagePath, $uniqueName, 'public');

        // Verificar que el archivo realmente exista en el disco público (Windows puede comportarse distinto con rutas)
        try {
            $absPath = Storage::disk('public')->path($filePath);
            if (!file_exists($absPath)) {
                // Fallback: forzar guardado usando putFileAs
                Log::warning("storeAs no materializó el archivo, intentando fallback putFileAs: {$filePath}");
                Storage::disk('public')->putFileAs($storagePath, $file, $uniqueName);
                // Recalcular path absoluto y verificar
                $absPath = Storage::disk('public')->path($filePath);
                if (!file_exists($absPath)) {
                    Log::error("No se pudo guardar el archivo en disco público: {$filePath} (abs: {$absPath})");
                } else {
                    Log::info("Archivo guardado mediante fallback en: {$absPath}");
                }
            } else {
                Log::info("Archivo guardado correctamente en: {$absPath}");
            }
        } catch (\Throwable $t) {
            Log::error('Error verificando o guardando archivo en disco público: ' . $t->getMessage());
        }
        
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

    /**
     * Convert HTML content to plain text with basic formatting
     */
    private function convertHtmlToText($html)
    {
        if (empty($html)) {
            return '';
        }

        // Usar Html2Text para convertir HTML a texto plano con formato
        $htmlConverter = new \Html2Text\Html2Text($html);
        $text = $htmlConverter->getText();
        
        return $text;
    }

    public function uploadAnnex(Request $request, $programId, $annexId)
    {
        // Verificar el tipo de anexo
        $annex = Annex::findOrFail($annexId);
        
        if ($annex->content_type === 'text') {
            // Validar para anexos de texto
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'content_text' => 'required|string|max:65535', // Text content
            ]);

            try {
                // Buscar si ya existe una submission para este anexo
                $submission = CompanyAnnexSubmission::where([
                    'company_id' => $validated['company_id'],
                    'program_id' => $programId,
                    'annex_id' => $annexId,
                ])->first();

                if ($submission) {
                    // Actualizar el contenido existente
                    $submission->update([
                        'content_text' => $validated['content_text'],
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                } else {
                    // Crear nueva submission
                    $submission = CompanyAnnexSubmission::create([
                        'company_id' => $validated['company_id'],
                        'program_id' => $programId,
                        'annex_id' => $annexId,
                        'content_text' => $validated['content_text'],
                        'file_path' => null,
                        'file_name' => null,
                        'mime_type' => 'text/plain',
                        'file_size' => strlen($validated['content_text']),
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Texto guardado exitosamente',
                    'submission' => [
                        'id' => $submission->id,
                        'content_text' => $submission->content_text,
                        'mime' => 'text/plain',
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Error saving text annex: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar el texto: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            // Validar para anexos de archivo/imagen
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            try {
                $file = $request->file('file');
                $submission = $this->saveAnnexFile(
                    $file,
                    $validated['company_id'],
                    $programId,
                    $annexId
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Archivo subido exitosamente',
                    'submission' => [
                        'id' => $submission->id,
                        'name' => $submission->file_name,
                        // Usar ruta interna /public-storage para evitar 403 con symlink en dev/Windows
                        'url' => url('public-storage/' . ltrim($submission->file_path, '/')),
                        'mime' => $submission->mime_type,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Error uploading annex file: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo: ' . $e->getMessage(),
                ], 500);
            }
        }
    }

    public function clearAnnexFiles(Request $request, $programId, $annexId)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        try {
            // Obtener todas las submisiones para este anexo
            $submissions = CompanyAnnexSubmission::where('company_id', $validated['company_id'])
                ->where('program_id', $programId)
                ->where('annex_id', $annexId)
                ->get();

            // Eliminar los archivos físicos del storage (solo si hay file_path)
            foreach ($submissions as $submission) {
                try {
                    // Solo intentar eliminar si hay un file_path (anexos de imagen/archivo)
                    if ($submission->file_path && Storage::disk('public')->exists($submission->file_path)) {
                        Storage::disk('public')->delete($submission->file_path);
                    }
                } catch (\Exception $e) {
                    Log::warning("No se pudo eliminar el archivo: {$submission->file_path}");
                }
            }

            // Eliminar los registros de la base de datos
            CompanyAnnexSubmission::where('company_id', $validated['company_id'])
                ->where('program_id', $programId)
                ->where('annex_id', $annexId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivos eliminados exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing annex files: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAnnexFile(Request $request, $programId, $annexId, $submissionId)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        try {
            // Verificar que la submisión pertenece a la empresa y al anexo correctos
            $submission = CompanyAnnexSubmission::where('id', $submissionId)
                ->where('company_id', $validated['company_id'])
                ->where('program_id', $programId)
                ->where('annex_id', $annexId)
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            // Eliminar el archivo físico del storage (solo si hay file_path)
            try {
                if ($submission->file_path && Storage::disk('public')->exists($submission->file_path)) {
                    Storage::disk('public')->delete($submission->file_path);
                }
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar el archivo: {$submission->file_path}");
            }

            // Eliminar el registro de la base de datos
            $submission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting annex file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $e->getMessage(),
            ], 500);
        }
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
            'anexos.*.id' => 'nullable|exists:annexes,id',
            'anexos.*.archivo' => 'nullable|file|max:10240'
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

            // Determinar la plantilla basada en template_path del programa
            if (!empty($program->template_path)) {
                // Usar la plantilla configurada en el programa
                $templatePath = storage_path('plantillas/' . $program->template_path);
            } elseif ($program->id === 1) {
                // Fallback para programas antiguos sin template_path configurado
                $templatePath = storage_path('plantillas/planDeSaneamientoBasico/Plantilla.docx');
            } else {
                throw new \Exception('Este programa no tiene una plantilla configurada. Por favor asigne una plantilla en la configuración del programa.');
            }

            if (!file_exists($templatePath)) {
                throw new \Exception("La plantilla no se encontró en: {$templatePath}");
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

            // Helper para derivar placeholder si no está definido en BD
            $derivePlaceholder = function (?Annex $annex) {
                if (!$annex) return null;
                if (!empty($annex->placeholder)) return $annex->placeholder;
                // Intentar extraer un número del código del anexo
                if (!empty($annex->codigo_anexo) && preg_match('/(\d+)/', $annex->codigo_anexo, $m)) {
                    return 'Anexo ' . $m[1];
                }
                // Intentar por nombre
                if (!empty($annex->nombre) && preg_match('/(\d+)/', $annex->nombre, $m)) {
                    return 'Anexo ' . $m[1];
                }
                return null;
            };

            // Procesar y guardar los anexos
            $annexSubmissions = [];
            
            // Primero, obtener los anexos ya guardados de la BD para esta empresa/programa
            $existingSubmissions = CompanyAnnexSubmission::where('company_id', $validated['company_id'])
                ->where('program_id', $program->id)
                ->whereIn('status', ['Pendiente', 'Aprobado'])
                ->get();
            
            // Si se envían nuevos archivos, procesarlos (aunque ahora esto casi nunca pasa porque subimos antes)
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
                        // Agregar a la colección de existentes
                        $existingSubmissions->push($submission);
                    }
                }
            }
            
            // Procesar todas las submisiones (nuevas y existentes) para insertar en el documento
            // Agrupar por annex_id para manejar múltiples imágenes por anexo
            $submissionsByAnnex = $existingSubmissions->groupBy('annex_id');
            
            $placeholdersWithImage = [];
            foreach ($submissionsByAnnex as $annexId => $submissions) {
                // Por ahora, usar solo la primera imagen de cada anexo (PhpWord limita a 1 por placeholder)
                // TODO: Implementar soporte para múltiples imágenes por anexo usando cloneBlock
                $submission = $submissions->first();
                
                $annexInfo = Annex::find($annexId);
                $placeholder = $derivePlaceholder($annexInfo);
                
                if (!$placeholder) {
                    continue; // Skip if no placeholder defined
                }

                // Verificar el content_type del anexo
                if ($annexInfo && $annexInfo->content_type === 'text') {
                    // Para anexos de tipo texto, buscar el texto en content_text de la submission
                    $htmlContent = $submission->content_text ?? '(Texto no proporcionado)';
                    
                    // Convertir HTML a texto plano para el documento
                    $textContent = $this->convertHtmlToText($htmlContent);
                    
                    $templateProcessor->setValue($placeholder, $textContent);
                    $placeholdersWithImage[$placeholder] = true;
                    
                    Log::info("Anexo de texto insertado en documento: {$annexInfo->nombre}");
                } elseif (str_starts_with($submission->mime_type, 'image/')) {
                    // Para anexos de tipo imagen, procesar como antes
                    try {
                        $imagePath = Storage::disk('public')->path($submission->file_path);
                    } catch (\Throwable $ex) {
                        $imagePath = storage_path('app/public/' . $submission->file_path);
                    }
                    
                    if (!file_exists($imagePath)) {
                        Log::warning("Archivo no encontrado al generar documento: {$imagePath}");
                        continue; // Skip si el archivo no existe
                    }
                    
                    $tempImagePaths[] = $imagePath;
                    
                    $templateProcessor->setImageValue($placeholder, [
                        'path' => $imagePath,
                        'width' => 500,
                        'ratio' => true
                    ]);
                    $placeholdersWithImage[$placeholder] = true;
                    
                    Log::info("Anexo de imagen insertado en documento: {$annexInfo->nombre} ({$submissions->count()} archivo(s) en BD, usando el primero)");
                }
            }

            // Asegurar que cada anexo del programa tenga algún valor en el documento, incluso si no hay imagen
            $programAnnexIds = DB::table('program_annexes')->where('program_id', $program->id)->pluck('annex_id')->toArray();
            $programAnnexes = Annex::whereIn('id', $programAnnexIds)->get();
            foreach ($programAnnexes as $ax) {
                $ph = $derivePlaceholder($ax);
                if ($ph && empty($placeholdersWithImage[$ph])) {
                    $templateProcessor->setValue($ph, '(Anexo no proporcionado)');
                }
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
            $contentText = null;
            
            if ($companyId) {
                $subs = CompanyAnnexSubmission::where('company_id', $companyId)
                    ->where('program_id', $program->id)
                    ->where('annex_id', $a->id)
                    ->whereIn('status', ['Pendiente', 'Aprobado'])
                    ->get();

                foreach ($subs as $s) {
                    // Si es un anexo de texto, obtener el contenido
                    if ($a->content_type === 'text') {
                        $contentText = $s->content_text;
                        continue; // No procesar como archivo
                    }
                    
                    // Para anexos de archivo/imagen, verificar que exista el archivo
                    if ($s->file_path) {
                        try {
                            $exists = Storage::disk('public')->exists($s->file_path);
                        } catch (\Throwable $t) {
                            $exists = file_exists(storage_path('app/public/' . ltrim($s->file_path, '/')));
                        }
                        if (!$exists) {
                            Log::warning("Archivo faltante en BD, omitido en vista: {$s->file_path} (submission {$s->id})");
                            continue;
                        }
                        $files[] = [
                            'id' => $s->id,
                            'name' => $s->file_name,
                            // Usar ruta interna /public-storage para evitar 403 con symlink en dev/Windows
                            'url' => url('public-storage/' . ltrim($s->file_path, '/')),
                            'mime' => $s->mime_type,
                        ];
                    }
                }
            }

            // Mapear tipo a la nomenclatura del frontend
            // El 'type' determina qué tipo de archivo aceptar
            // El 'content_type' determina si es imagen/archivo o texto
            // Por defecto, todos los anexos aceptan imágenes, a menos que sean de tipo texto
            if ($a->content_type === 'text') {
                // Para anexos de texto, usar IMAGES como tipo por defecto (el frontend decidirá mostrar editor)
                $type = 'IMAGES';
            } else {
                // Para anexos de imagen/archivo, siempre usar IMAGES
                $type = 'IMAGES';
            }

            return [
                'id' => $a->id,
                'name' => $a->nombre,
                'type' => $type,
                'content_type' => $a->content_type ?? 'image', // Incluir content_type (image/text)
                'content_text' => $contentText, // Incluir texto si existe
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