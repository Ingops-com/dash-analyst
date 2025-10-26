<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Program;
use App\Models\Annex;
use App\Models\CompanyAnnexSubmission;
use App\Models\CompanyPoeRecord;
use App\Models\Poe;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Company;

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
            'submitted_by' => auth()->id()
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

            // Determinar la plantilla basada en el tipo de programa
            // Si no existe la columna template_type en la BD, permitimos usar el mapeo por id (programa 1 => plan_saneamiento)
            if (isset($program->template_type) && $program->template_type === 'plan_saneamiento') {
                $templatePath = storage_path('plantillas/planDeSaneamientoBasico/Plantilla.docx');
            } elseif ($program->id === 1) {
                // Fallback para entorno de pruebas: programa 1 usa plan de saneamiento básico
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
                // placeholder adicional por si aparece
                'Anexo 6' => '(Anexo no proporcionado)',
            ];

            // Reemplazar variables comunes tal cual están en la plantilla (${Nombre de la empresa} etc.)
            foreach ($commonVariables as $key => $value) {
                $templateProcessor->setValue($key, $value ?? '');
            }

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
                            // Obtener path físico del archivo guardado
                            try {
                                $imagePath = Storage::path($submission->file_path);
                            } catch (\Throwable $ex) {
                                // Fallback: construir desde storage_path
                                $imagePath = storage_path('app/' . ltrim($submission->file_path, '/'));
                            }
                            $tempImagePaths[] = $imagePath;

                            // Obtener información del anexo para mapear placeholder
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

            $templateProcessor->saveAs($finalDocxPath);
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