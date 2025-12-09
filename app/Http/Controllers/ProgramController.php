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
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

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

    private function saveScreenshot($dataUrl, $companyId, $programId, $annexId, $annexName = null)
    {
        try {
            if (empty($dataUrl)) {
                throw new \Exception('Screenshot data is empty');
            }

            Log::info('Saving screenshot', [
                'company_id' => $companyId,
                'program_id' => $programId,
                'annex_id' => $annexId,
                'annex_name' => $annexName,
                'data_url_length' => strlen($dataUrl),
                'data_url_start' => substr($dataUrl, 0, 50),
            ]);

            // Decodificar el Data URL
            if (strpos($dataUrl, 'data:image/png;base64,') === 0) {
                $imageData = base64_decode(str_replace('data:image/png;base64,', '', $dataUrl));
            } else {
                throw new \Exception('Invalid screenshot format: ' . substr($dataUrl, 0, 100));
            }

            if (empty($imageData)) {
                throw new \Exception('Failed to decode image data');
            }

            // Usar el nombre del anexo o generar uno por defecto
            if ($annexName) {
                // Limpiar el nombre del anexo: remover caracteres especiales
                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $annexName);
                $fileName = $cleanName . '.png';
            } else {
                $fileName = 'screenshot_' . uniqid() . '.png';
            }
            
            $storagePath = "planilla-screenshots/company_{$companyId}/program_{$programId}";
            $fullPath = "{$storagePath}/{$fileName}";
            
            // Guardar el archivo en el disco 'public'
            $saved = Storage::disk('public')->put($fullPath, $imageData);
            
            if (!$saved) {
                throw new \Exception('Failed to save file to storage');
            }

            Log::info('Screenshot saved successfully', [
                'company_id' => $companyId,
                'program_id' => $programId,
                'annex_id' => $annexId,
                'file_path' => $fullPath,
                'file_size' => strlen($imageData),
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Error saving screenshot: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'program_id' => $programId,
                'annex_id' => $annexId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function uploadAnnex(Request $request, $programId, $annexId)
    {
        // Verificar el tipo de anexo
        $annex = Annex::findOrFail($annexId);

        // Determinar el tipo de contenido a procesar
        $treatAsText = ($annex->content_type === 'text') || ($request->has('content_text') && !$request->hasFile('file'));
        $treatAsTable = ($annex->content_type === 'table') || $request->has('table_data');
        $treatAsPlanilla = ($annex->content_type === 'planilla') || $request->has('planilla_data');

        Log::info('uploadAnnex called', [
            'program_id' => $programId,
            'annex_id' => $annexId,
            'annex_content_type' => $annex->content_type,
            'has_file' => $request->hasFile('file'),
            'has_content_text' => $request->has('content_text'),
            'has_table_data' => $request->has('table_data'),
            'has_planilla_data' => $request->has('planilla_data'),
            'treat_as_text' => $treatAsText,
            'treat_as_table' => $treatAsTable,
            'treat_as_planilla' => $treatAsPlanilla,
        ]);

        if ($treatAsTable) {
            // Validar para anexos de tabla
            try {
                $validated = $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'table_data' => 'required|array|min:1',
                    'table_data.*' => 'array',
                ]);
            } catch (ValidationException $ve) {
                Log::warning('Validation failed for table annex', [
                    'errors' => $ve->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $ve->errors(),
                ], 422);
            }

            try {
                // Buscar si ya existe una submission para este anexo
                $submission = CompanyAnnexSubmission::where([
                    'company_id' => $validated['company_id'],
                    'program_id' => $programId,
                    'annex_id' => $annexId,
                ])->first();

                // Convertir los datos de la tabla a JSON
                $tableDataJson = json_encode($validated['table_data']);

                if ($submission) {
                    // Actualizar el contenido existente
                    $submission->update([
                        'content_text' => $tableDataJson,
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                } else {
                    // Crear nueva submission
                    $submission = CompanyAnnexSubmission::create([
                        'company_id' => $validated['company_id'],
                        'program_id' => $programId,
                        'annex_id' => $annexId,
                        'content_text' => $tableDataJson,
                        'file_path' => null,
                        'file_name' => null,
                        'mime_type' => 'application/json',
                        'file_size' => strlen($tableDataJson),
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Tabla guardada exitosamente',
                    'submission' => [
                        'id' => $submission->id,
                        'table_data' => $validated['table_data'],
                        'mime' => 'application/json',
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Error saving table annex: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la tabla: ' . $e->getMessage(),
                ], 500);
            }
        } elseif ($treatAsPlanilla) {
            // Validar para anexos tipo planilla
            try {
                $validated = $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'planilla_data' => 'required|array',
                    'screenshot_data' => 'nullable|string', // Data URL del screenshot
                    'screenshot_filename' => 'nullable|string', // Nombre del anexo para el archivo
                ]);
            } catch (ValidationException $ve) {
                Log::warning('Validation failed for planilla annex', [
                    'errors' => $ve->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $ve->errors(),
                ], 422);
            }

            try {
                // Buscar si ya existe una submission para este anexo
                $submission = CompanyAnnexSubmission::where([
                    'company_id' => $validated['company_id'],
                    'program_id' => $programId,
                    'annex_id' => $annexId,
                ])->first();

                // Convertir los datos de la planilla a JSON
                $planillaDataJson = json_encode($validated['planilla_data']);
                
                // Obtener el nombre del anexo desde la solicitud o de la base de datos
                $annexName = $validated['screenshot_filename'] ?? null;
                if (!$annexName) {
                    $annex = Annex::find($annexId);
                    $annexName = $annex ? $annex->name : null;
                }
                
                // Procesar y guardar el screenshot si existe
                $screenshotPath = null;
                if (!empty($validated['screenshot_data'])) {
                    Log::info('Processing screenshot for planilla', [
                        'has_screenshot_data' => !empty($validated['screenshot_data']),
                        'screenshot_data_length' => strlen($validated['screenshot_data'] ?? ''),
                        'annex_name' => $annexName,
                    ]);
                    try {
                        $screenshotPath = $this->saveScreenshot(
                            $validated['screenshot_data'],
                            $validated['company_id'],
                            $programId,
                            $annexId,
                            $annexName
                        );
                    } catch (\Exception $e) {
                        Log::warning('Error saving screenshot: ' . $e->getMessage());
                        // Continuar aunque falle el screenshot
                    }
                } else {
                    Log::info('No screenshot data provided for planilla', [
                        'company_id' => $validated['company_id'],
                        'program_id' => $programId,
                        'annex_id' => $annexId,
                    ]);
                }

                if ($submission) {
                    // Actualizar el contenido existente
                    $submission->update([
                        'content_text' => $planillaDataJson,
                        'screenshot_path' => $screenshotPath,
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                } else {
                    // Crear nueva submission
                    $submission = CompanyAnnexSubmission::create([
                        'company_id' => $validated['company_id'],
                        'program_id' => $programId,
                        'annex_id' => $annexId,
                        'content_text' => $planillaDataJson,
                        'screenshot_path' => $screenshotPath,
                        'file_path' => null,
                        'file_name' => null,
                        'mime_type' => 'application/json',
                        'file_size' => strlen($planillaDataJson),
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Planilla guardada exitosamente',
                    'submission' => [
                        'id' => $submission->id,
                        'planilla_data' => $validated['planilla_data'],
                        'screenshot_path' => $screenshotPath,
                        'mime' => 'application/json',
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Error saving planilla annex: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la planilla: ' . $e->getMessage(),
                ], 500);
            }
        } elseif ($treatAsText) {
            // Validar para anexos de texto
            try {
                $validated = $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'content_text' => 'required|string|max:65535', // Text content
                ]);
            } catch (ValidationException $ve) {
                Log::warning('Validation failed for text annex', [
                    'errors' => $ve->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $ve->errors(),
                ], 422);
            }

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
                        'content_text' => $request->input('content_text'),
                        'status' => 'Pendiente',
                        'submitted_by' => Auth::id(),
                    ]);
                } else {
                    // Crear nueva submission
                    $submission = CompanyAnnexSubmission::create([
                        'company_id' => $validated['company_id'],
                        'program_id' => $programId,
                        'annex_id' => $annexId,
                        'content_text' => $request->input('content_text'),
                        'file_path' => null,
                        'file_name' => null,
                        'mime_type' => 'text/plain',
                        'file_size' => strlen((string)$request->input('content_text')),
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
            try {
                $validated = $request->validate([
                    'company_id' => 'required|exists:companies,id',
                    'file' => 'required|file|max:10240', // 10MB max
                ]);
            } catch (ValidationException $ve) {
                Log::warning('Validation failed for file/image annex', [
                    'errors' => $ve->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $ve->errors(),
                ], 422);
            }

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
                $annexInfo = Annex::find($annexId);
                $placeholder = $derivePlaceholder($annexInfo);
                if (!$placeholder) { continue; }

                // Reunir datos de header comunes (usar la fecha más reciente entre las submissions)
                $latestSubmission = $submissions->sortByDesc('updated_at')->first();
                $annexHeaderData = [
                    'name' => $annexInfo?->nombre,
                    'code' => $annexInfo?->codigo_anexo,
                    'uploaded_at' => $latestSubmission && $latestSubmission->updated_at ? $latestSubmission->updated_at->format('Y-m-d H:i') : null,
                ];

                // Generar PNG de metadata SIEMPRE
                $metaImagePath = $this->generateAnnexMetadataImage($companyData, $annexHeaderData);
                $tempImagePaths[] = $metaImagePath;

                if ($annexInfo && $annexInfo->content_type === 'text') {
                    // Para anexos de texto: SIEMPRE insertar como texto directo (NO como imagen)
                    try {
                        // Obtener el contenido de texto
                        $textSubmission = $submissions->first();
                        $textContent = $textSubmission?->content_text ?? '';
                        
                        Log::info("Procesando anexo de texto (inserción directa): {$annexInfo->nombre}");
                        
                        if (!empty($textContent)) {
                            // Limpiar HTML y preparar texto
                            $cleanText = html_entity_decode(strip_tags($textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $cleanText = str_replace(["\r\n", "\r"], "\n", $cleanText);
                            $cleanText = trim($cleanText);
                            
                            // Insertar texto directamente en el documento
                            $templateProcessor->setValue($placeholder, $cleanText);
                            
                            $placeholdersWithImage[$placeholder] = true;
                            Log::info("Anexo de texto insertado directamente (no como imagen): {$annexInfo->nombre}");
                        } else {
                            // Sin contenido
                            $templateProcessor->setValue($placeholder, '(Sin contenido)');
                            $placeholdersWithImage[$placeholder] = true;
                        }
                    } catch (\Throwable $e) {
                        $templateProcessor->setValue($placeholder, '(Error en anexo de texto)');
                        $placeholdersWithImage[$placeholder] = true;
                        Log::warning("Error generando anexo de texto: {$annexInfo->nombre} -> {$e->getMessage()}");
                    }
                    continue;
                }

                if ($annexInfo && $annexInfo->content_type === 'table') {
                    // Para anexos de tabla: generar imagen cuadrada completa (metadata + tabla)
                    try {
                        // Obtener los datos de la tabla
                        $tableSubmission = $submissions->first();
                        $tableDataJson = $tableSubmission?->content_text ?? '';
                        
                        if (!empty($tableDataJson) && !empty($annexInfo->table_columns)) {
                            $tableData = json_decode($tableDataJson, true);
                            
                            if (is_array($tableData) && count($tableData) > 0) {
                                // Generar imagen cuadrada completa con metadata + tabla
                                $completeImagePath = $this->generateTableWithMetadataImage(
                                    $tableData,
                                    $annexInfo->table_columns,
                                    $annexInfo->table_header_color ?? '#153366',
                                    [
                                        'nombre' => $annexInfo->nombre,
                                        'codigo' => $annexInfo->codigo_anexo,
                                        'descripcion' => $annexInfo->descripcion,
                                        'uploaded_at' => $tableSubmission->updated_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i')
                                    ],
                                    $companyData
                                );
                                $tempImagePaths[] = $completeImagePath;
                                
                                // Crear copia única
                                $uniquePath = $this->createUniqueImageCopy($completeImagePath);
                                $tempImagePaths[] = $uniquePath;
                                
                                // Insertar con tamaño exacto
                                $templateProcessor->setImageValue($placeholder, [
                                    'path' => $uniquePath,
                                    'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                    'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                    'ratio' => false,
                                    'wrappingStyle' => 'inline',
                                    'positioning' => 'relative',
                                    'posHorizontalRel' => 'margin',
                                    'posVerticalRel' => 'line'
                                ]);
                                $placeholdersWithImage[$placeholder] = true;
                                Log::info("Anexo de tabla completo (metadata + datos) insertado: {$annexInfo->nombre}");
                            } else {
                                // Sin datos válidos, solo metadata
                                $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                                $tempImagePaths[] = $uniquePath;
                                
                                $templateProcessor->setImageValue($placeholder, [
                                    'path' => $uniquePath,
                                    'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                    'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                    'ratio' => false,
                                    'wrappingStyle' => 'inline',
                                    'positioning' => 'relative',
                                    'posHorizontalRel' => 'margin',
                                    'posVerticalRel' => 'line'
                                ]);
                                $placeholdersWithImage[$placeholder] = true;
                                Log::info("Anexo de tabla sin datos válidos, solo metadata: {$annexInfo->nombre}");
                            }
                        } else {
                            // Sin contenido, solo metadata
                            $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                            $tempImagePaths[] = $uniquePath;
                            
                            $templateProcessor->setImageValue($placeholder, [
                                'path' => $uniquePath,
                                'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                'ratio' => false,
                                'wrappingStyle' => 'inline',
                                'positioning' => 'relative',
                                'posHorizontalRel' => 'margin',
                                'posVerticalRel' => 'line'
                            ]);
                            $placeholdersWithImage[$placeholder] = true;
                            Log::info("Anexo de tabla sin contenido, solo metadata: {$annexInfo->nombre}");
                        }
                    } catch (\Throwable $e) {
                        $templateProcessor->setValue($placeholder, '(Anexo de tabla)');
                        $placeholdersWithImage[$placeholder] = true;
                        Log::warning("Error generando anexo de tabla: {$annexInfo->nombre} -> {$e->getMessage()}");
                    }
                    continue;
                }

                if ($annexInfo && $annexInfo->content_type === 'pdf') {
                    // Para anexos tipo PDF: extraer páginas como imágenes e insertar
                    try {
                        $pdfSubmission = $submissions->first();
                        
                        if ($pdfSubmission && $pdfSubmission->file_path) {
                            try {
                                $pdfPath = Storage::disk('public')->path($pdfSubmission->file_path);
                            } catch (\Throwable $t) {
                                $pdfPath = storage_path('app/public/' . $pdfSubmission->file_path);
                            }
                            
                            if (file_exists($pdfPath)) {
                                Log::info("Procesando anexo PDF: {$annexInfo->nombre}", ['path' => $pdfPath]);
                                
                                // Extraer páginas del PDF como imágenes
                                $pdfPageImages = $this->extractPdfPagesToImages($pdfPath);
                                
                                if (!empty($pdfPageImages)) {
                                    // Combinar metadata + todas las páginas del PDF verticalmente
                                    $combinedImagePath = $this->combineImagesVertically($metaImagePath, $pdfPageImages);
                                    $tempImagePaths[] = $combinedImagePath;
                                    $tempImagePaths = array_merge($tempImagePaths, $pdfPageImages);
                                    
                                    // Crear copia única
                                    $uniquePath = $this->createUniqueImageCopy($combinedImagePath);
                                    $tempImagePaths[] = $uniquePath;
                                    
                                    // Insertar imagen combinada
                                    $templateProcessor->setImageValue($placeholder, [
                                        'path' => $uniquePath,
                                        'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                        'ratio' => true,
                                        'wrappingStyle' => 'inline',
                                        'positioning' => 'relative',
                                        'posHorizontalRel' => 'margin',
                                        'posVerticalRel' => 'line'
                                    ]);
                                    $placeholdersWithImage[$placeholder] = true;
                                    Log::info("Anexo PDF insertado con " . count($pdfPageImages) . " página(s): {$annexInfo->nombre}");
                                } else {
                                    // No se pudieron extraer páginas, solo metadata
                                    $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                                    $tempImagePaths[] = $uniquePath;
                                    
                                    $templateProcessor->setImageValue($placeholder, [
                                        'path' => $uniquePath,
                                        'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                        'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                                        'ratio' => false,
                                        'wrappingStyle' => 'inline',
                                        'positioning' => 'relative',
                                        'posHorizontalRel' => 'margin',
                                        'posVerticalRel' => 'line'
                                    ]);
                                    $placeholdersWithImage[$placeholder] = true;
                                    Log::warning("No se pudieron extraer páginas del PDF, solo metadata: {$annexInfo->nombre}");
                                }
                            } else {
                                // Archivo PDF no existe
                                $templateProcessor->setValue($placeholder, '(PDF no encontrado)');
                                $placeholdersWithImage[$placeholder] = true;
                                Log::warning("Archivo PDF no encontrado: {$pdfPath}");
                            }
                        } else {
                            // Sin PDF cargado, omitir (o poner solo metadata)
                            $templateProcessor->setValue($placeholder, '');
                            $placeholdersWithImage[$placeholder] = true;
                            Log::info("Anexo PDF sin archivo cargado, placeholder vacío: {$annexInfo->nombre}");
                        }
                    } catch (\Throwable $e) {
                        $templateProcessor->setValue($placeholder, '(Error en anexo PDF)');
                        $placeholdersWithImage[$placeholder] = true;
                        Log::error("Error procesando anexo PDF: {$annexInfo->nombre} -> {$e->getMessage()}");
                    }
                    continue;
                }

                // Caso imágenes / archivos: reunir imágenes del anexo
                $annexImages = [];
                foreach ($submissions as $s) {
                    if (!$s->file_path || !str_starts_with($s->mime_type, 'image/')) continue;
                    try {
                        $candidate = Storage::disk('public')->path($s->file_path);
                    } catch (\Throwable $t) {
                        $candidate = storage_path('app/public/' . $s->file_path);
                    }
                    if (file_exists($candidate)) {
                        $annexImages[] = $candidate;
                    } else {
                        Log::warning("Imagen de anexo no encontrada: {$candidate}");
                    }
                }

                // Para anexos tipo imagen: combinar metadata con las imágenes subidas
                try {
                    $finalImagePath = null;
                    
                    // Si hay imágenes subidas, combinarlas verticalmente con la metadata
                    if (!empty($annexImages)) {
                        $finalImagePath = $this->combineImagesVertically($metaImagePath, $annexImages);
                        $tempImagePaths[] = $finalImagePath;
                        Log::info("Anexo combinado (metadata + imágenes)", [
                            'nombre' => $annexInfo?->nombre,
                            'num_imagenes' => count($annexImages)
                        ]);
                        
                        // Imagen combinada cuadrada - usar ancho completo con ratio para mantener proporciones
                        $templateProcessor->setImageValue($placeholder, [
                            'path' => $finalImagePath,
                            'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                            'ratio' => true, // Mantener proporciones - altura se calculará automáticamente
                            'wrappingStyle' => 'inline',
                            'positioning' => 'relative',
                            'posHorizontalRel' => 'margin',
                            'posVerticalRel' => 'line'
                        ]);
                    } else {
                        // Sin imágenes, solo usar metadata (cuadrada 2400x2400)
                        $finalImagePath = $this->createUniqueImageCopy($metaImagePath);
                        $tempImagePaths[] = $finalImagePath;
                        Log::info("Anexo solo metadata (sin imágenes subidas): {$annexInfo?->nombre}");
                        
                        // Metadata sola - forzar cuadrado
                        $templateProcessor->setImageValue($placeholder, [
                            'path' => $finalImagePath,
                            'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                            'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18),
                            'ratio' => false, // No ratio para mantener cuadrado exacto
                            'wrappingStyle' => 'inline',
                            'positioning' => 'relative',
                            'posHorizontalRel' => 'margin',
                            'posVerticalRel' => 'line'
                        ]);
                    }
                    
                    $placeholdersWithImage[$placeholder] = true;
                } catch (\Throwable $e) {
                    $templateProcessor->setValue($placeholder, '(Anexo no proporcionado)');
                    Log::warning("Error insertando anexo de imagen: {$e->getMessage()}");
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
            
            // Establecer metadatos del DOCX (título/autor) para que el PDF herede un título correcto incluso con LibreOffice
            try {
                $docTitle = trim(($companyData['nombre'] ?? 'Empresa') . ' - ' . ($programData['nombre'] ?? 'Programa'));
                $docCreator = $companyData['nombre'] ?? 'Dash Analyst';
                $this->setDocxCoreProperties($finalDocxPath, $docTitle, $docCreator);
                Log::info('Metadatos DOCX actualizados (core.xml)');
            } catch (\Throwable $metaEx) {
                Log::warning('No se pudieron establecer metadatos en DOCX: ' . $metaEx->getMessage());
            }
            
            // NO sanitizar el DOCX para mantener todos los estilos y formato original
            // LibreOffice maneja correctamente las imágenes WMF/EMF
            Log::info('DOCX procesado con todos los estilos originales preservados');

            // === Persistir el documento en una ruta controlada y generar PDF ===
            try {
                $disk = Storage::disk('public');
                // Raíz por empresa/programa
                $baseRoot = 'company-documents/company_' . $company->id . '/program_' . $program->id;
                $historyDir = $baseRoot . '/history';
                if (!$disk->exists($historyDir)) {
                    $disk->makeDirectory($historyDir);
                }

                $safeCompany = Str::slug((string)($company->nombre ?? 'empresa')) ?: 'empresa';
                $safeProgram = Str::slug((string)($program->codigo ?? $program->nombre ?? 'programa')) ?: 'programa';
                $timestamp = date('Ymd_His');

                // Archivos de historial con timestamp
                $docxHistoryRel = $historyDir . '/' . $safeCompany . '_' . $safeProgram . '_' . $timestamp . '.docx';
                $pdfHistoryRel = $historyDir . '/' . $safeCompany . '_' . $safeProgram . '_' . $timestamp . '.pdf';

                // Archivos 'current'
                $docxCurrentRel = $baseRoot . '/current.docx';
                $pdfCurrentRel = $baseRoot . '/current.pdf';

                // Guardar DOCX en historial y actualizar 'current.docx'
                try {
                    $docxContents = @file_get_contents($finalDocxPath);
                    if ($docxContents !== false) {
                        $disk->put($docxHistoryRel, $docxContents);
                        $disk->put($docxCurrentRel, $docxContents);
                        Log::info('DOCX guardado (historial y current)', ['history' => $docxHistoryRel, 'current' => $docxCurrentRel]);
                    } else {
                        Log::warning('No se pudo leer el DOCX temporal para persistirlo');
                    }
                } catch (\Throwable $e) {
                    Log::warning('Fallo al guardar DOCX en storage público: ' . $e->getMessage());
                }

                // Generar PDF desde el DOCX usando Microsoft Word (COM) si está disponible en Windows
                try {
                    Log::info('Iniciando conversión de DOCX a PDF', ['docx_path' => $finalDocxPath]);
                    
                    $pdfGenerated = false;
                    // Usar directorio temp del storage en lugar de sys_get_temp_dir()
                    $tempStorageDir = storage_path('app/temp');
                    if (!File::isDirectory($tempStorageDir)) {
                        File::makeDirectory($tempStorageDir, 0755, true);
                    }
                    // Normalizar la ruta para Windows (usar backslashes)
                    $tempStorageDir = str_replace('/', '\\', $tempStorageDir);
                    $tempPdfPath = $tempStorageDir . '\\temp_pdf_' . uniqid() . '.pdf';

                    // 1) Preferir Microsoft Word COM en Windows para máxima fidelidad de estilos
                    $docxAbsPath = realpath($finalDocxPath);
                    if (!$docxAbsPath) {
                        throw new \RuntimeException('No se pudo obtener ruta absoluta del DOCX');
                    }

                    $usedEngine = null;
                    if ($this->hasMsWordCom()) {
                        try {
                            $title = trim(($company->nombre ?? 'Empresa') . ' - ' . ($program->nombre ?? 'Programa'));
                            $this->convertDocxToPdfWithMsWord($docxAbsPath, $tempPdfPath, $title, $company->nombre ?? null);
                            if (file_exists($tempPdfPath) && filesize($tempPdfPath) > 1024) {
                                $pdfGenerated = true;
                                $usedEngine = 'MsWord';
                                Log::info('PDF exportado con Microsoft Word COM', ['pdf_path' => $tempPdfPath, 'size_kb' => round(filesize($tempPdfPath)/1024,2)]);
                            } else {
                                Log::warning('Microsoft Word COM no generó un PDF válido, se intentará LibreOffice');
                            }
                        } catch (\Throwable $mw) {
                            Log::warning('Fallo conversión con Microsoft Word COM: ' . $mw->getMessage());
                        }
                    }

                    // 2) Usar endpoint HTTP para conversión DOCX → PDF (rápido y fiel a estilos)
                    if (!$pdfGenerated) {
                        try {
                            Log::info('Convirtiendo a PDF con endpoint HTTP...');
                            
                            Log::info('Ruta DOCX absoluta: ' . $docxAbsPath);
                            Log::info('Directorio de salida: ' . $tempStorageDir);
                            
                            $httpOutputPath = $this->convertDocxToPdfWithLibreOffice($docxAbsPath, $tempStorageDir);

                            if (!empty($httpOutputPath) && file_exists($httpOutputPath) && @filesize($httpOutputPath) > 1024) {
                                // Mover a tempPdfPath para guardado unificado
                                if (@rename($httpOutputPath, $tempPdfPath) || @copy($httpOutputPath, $tempPdfPath)) {
                                    if ($httpOutputPath !== $tempPdfPath) { @unlink($httpOutputPath); }
                                    $pdfGenerated = true;
                                    $usedEngine = 'HTTP-Endpoint';
                                    Log::info('PDF generado exitosamente con endpoint HTTP', ['pdf_path' => $tempPdfPath, 'size_kb' => round(filesize($tempPdfPath)/1024, 2)]);
                                } else {
                                    Log::error('No se pudo mover el PDF generado por endpoint HTTP', ['from' => $httpOutputPath, 'to' => $tempPdfPath]);
                                }
                            } else {
                                Log::warning('Endpoint HTTP no devolvió un PDF válido');
                            }
                        } catch (\Throwable $httpErr) {
                            Log::error('Conversión con endpoint HTTP falló: ' . $httpErr->getMessage());
                            Log::error('Stack trace: ' . $httpErr->getTraceAsString());
                        }
                    }
                    
                    // Si no se generó aún, probar último recurso: DOCX -> HTML -> PDF (baja fidelidad)
                    if (!$pdfGenerated) {
                        try {
                            Log::info('Intentando fallback HTML->PDF con PhpWord + Dompdf (baja fidelidad)');
                            // Saneamos el DOCX para remover imágenes WMF/EMF que PhpWord no soporta
                            $docxForFallback = $this->sanitizeDocxImages($finalDocxPath);
                            if ($docxForFallback !== $finalDocxPath) {
                                Log::info('Usando DOCX saneado para fallback (WMF/EMF removidos)', ['path' => $docxForFallback]);
                            }
                            $phpWordDoc = \PhpOffice\PhpWord\IOFactory::load($docxForFallback, 'Word2007');
                            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordDoc, 'HTML');
                            $tempHtml = $tempStorageDir . '\\temp_doc_' . uniqid() . '.html';
                            $htmlWriter->save($tempHtml);
                            $html = @file_get_contents($tempHtml);
                            if ($html !== false && strlen($html) > 0) {
                                // Inyectar un CSS mínimo para mejorar tipografía/tablas en el fallback
                                $fallbackCss = "<style>body{font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#000;} h1,h2,h3{font-weight:bold;} p{margin:0 0 8px 0;} table{border-collapse:collapse; width:100%;} td,th{border:1px solid #000; padding:4px; vertical-align:top;} img{max-width:100%;}</style>";
                                if (stripos($html, '<head>') !== false) {
                                    $html = preg_replace('/<head>/i', '<head>' . $fallbackCss, $html, 1);
                                } else {
                                    $html = $fallbackCss . $html;
                                }
                                $dompdfOptions = new Options();
                                $dompdfOptions->set('isRemoteEnabled', true);
                                $dompdfOptions->set('isHtml5ParserEnabled', true);
                                $dompdfOptions->set('defaultFont', 'Arial');
                                $dompdfOptions->set('isFontSubsettingEnabled', true);
                                $dompdf = new Dompdf($dompdfOptions);
                                $dompdf->loadHtml($html);
                                $dompdf->setPaper('A4', 'portrait');
                                $dompdf->render();
                                $pdfData = $dompdf->output();
                                if ($pdfData && strlen($pdfData) > 1024) {
                                    $disk->put($pdfHistoryRel, $pdfData);
                                    $disk->put($pdfCurrentRel, $pdfData);
                                    $pdfGenerated = true;
                                    $usedEngine = 'HTML-Dompdf';
                                    Log::info('PDF guardado exitosamente (fallback HTML->PDF)', [
                                        'history' => $pdfHistoryRel,
                                        'current' => $pdfCurrentRel,
                                        'size_kb' => round(strlen($pdfData) / 1024, 2),
                                        'engine' => $usedEngine,
                                    ]);
                                } else {
                                    Log::warning('Fallback HTML->PDF produjo salida vacía o demasiado pequeña');
                                }
                            } else {
                                Log::warning('No se pudo generar HTML desde DOCX en fallback');
                            }
                            @unlink($tempHtml ?? '');
                        } catch (\Throwable $h) {
                            Log::warning('Fallback HTML->PDF falló: ' . $h->getMessage());
                        }
                    }

                    // Guardar PDF en storage si se generó exitosamente (para motores que escriben a tempPdfPath)
                    if ($pdfGenerated) {
                        if ($usedEngine === 'MsWord' || $usedEngine === 'HTTP-Endpoint') {
                            if (file_exists($tempPdfPath)) {
                                $pdfContents = @file_get_contents($tempPdfPath);
                                if ($pdfContents !== false && strlen($pdfContents) > 0) {
                                    $disk->put($pdfHistoryRel, $pdfContents);
                                    $disk->put($pdfCurrentRel, $pdfContents);
                                    Log::info('PDF guardado exitosamente', [
                                        'history' => $pdfHistoryRel,
                                        'current' => $pdfCurrentRel,
                                        'size_bytes' => strlen($pdfContents),
                                        'size_kb' => round(strlen($pdfContents) / 1024, 2),
                                        'engine' => $usedEngine,
                                    ]);
                                } else {
                                    Log::warning('El PDF generado está vacío o no se pudo leer');
                                }
                            } else {
                                Log::warning('Se indicó PDF generado pero no existe el archivo temporal esperado', ['engine' => $usedEngine, 'temp' => $tempPdfPath]);
                            }
                        } else {
                            // HTML-Dompdf ya guardó directo en storage
                            Log::info('PDF generado y guardado por fallback HTML->PDF');
                        }
                    } else {
                        Log::info('PDF no generado. El documento DOCX está disponible para descarga.');
                    }
                    
                    // Limpiar archivo temporal
                    @unlink($tempPdfPath);
                    
                } catch (\Throwable $e) {
                    Log::error('Error al generar PDF desde DOCX', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    Log::error('Stack trace: ' . $e->getTraceAsString());
                }

                // Rotación: mantener solo los últimos 3 DOCX/PDF en el historial
                try {
                    $allDocx = collect($disk->files($historyDir))
                        ->filter(fn($p) => str_ends_with(strtolower($p), '.docx'))
                        ->map(fn($p) => ['path' => $p, 'mtime' => @filemtime($disk->path($p)) ?: 0])
                        ->sortByDesc('mtime')
                        ->values();
                    if ($allDocx->count() > 3) {
                        $toDelete = $allDocx->slice(3)->all();
                        foreach ($toDelete as $f) {
                            try { $disk->delete($f['path']); } catch (\Throwable $ex) {}
                            // eliminar PDF hermano si existe
                            $pdfPair = preg_replace('/\.docx$/i', '.pdf', $f['path']);
                            if ($pdfPair && $disk->exists($pdfPair)) { try { $disk->delete($pdfPair); } catch (\Throwable $ex) {} }
                        }
                        Log::info('Rotación de historial completada', ['eliminados' => array_map(fn($x) => $x['path'], $toDelete)]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error durante la rotación de historial: ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                Log::warning('Persistencia de documentos omitida por error: ' . $e->getMessage());
            }

            // Entregar el archivo DOCX generado al usuario (manteniendo la descarga actual)
            return response()->download($finalDocxPath, 'Plan-Generado.docx')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error Final: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Error interno al generar el documento: ' . $e->getMessage()], 500);
        } finally {
            // Limpiar todas las imágenes temporales generadas
            foreach ($tempImagePaths as $path) {
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
            
            // Limpiar también las copias únicas creadas en buildFullWidthImageBlock
            $tempDir = storage_path('app/temp');
            if (File::isDirectory($tempDir)) {
                $tempFiles = File::glob($tempDir . '/img_*.{png,jpg,jpeg}', GLOB_BRACE);
                foreach ($tempFiles as $file) {
                    // Solo eliminar archivos recientes (últimos 2 minutos) para evitar conflictos
                    if (File::exists($file) && (time() - File::lastModified($file)) < 120) {
                        File::delete($file);
                    }
                }
            }
        }
    }

    /**
     * Verificar si LibreOffice está disponible en el sistema
     */
    private function hasLibreOffice(): bool
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: buscar en rutas comunes
                $paths = [
                    'C:\Program Files\LibreOffice\program\soffice.exe',
                    'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
                ];
                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        return true;
                    }
                }
                return false;
            } else {
                // Linux/Mac: verificar con which
                exec('which soffice', $output, $returnVar);
                return $returnVar === 0;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Verificar si está disponible Microsoft Word vía COM (Windows)
     */
    private function hasMsWordCom(): bool
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                return false;
            }
            if (!class_exists('COM')) {
                return false;
            }
            // Intentar crear instancia de Word sin mantenerla viva
            try {
                $word = new \COM('Word.Application');
                if ($word) {
                    // Cerrar inmediatamente
                    $word->Quit(false);
                    unset($word);
                    return true;
                }
            } catch (\Throwable $e) {
                return false;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Convertir DOCX a PDF usando endpoint HTTP de conversión
     */
    private function convertDocxToPdfWithLibreOffice(string $docxPath, string $outputDir): ?string
    {
        if (!file_exists($docxPath)) {
            throw new \RuntimeException("DOCX no existe: {$docxPath}");
        }

        if (!is_dir($outputDir)) {
            throw new \RuntimeException("Directorio de salida no existe: {$outputDir}");
        }

        $conversionEndpoint = env('PDF_CONVERSION_ENDPOINT', 'http://178.16.141.125:5050/convert');
        
        Log::info('Convirtiendo DOCX a PDF usando endpoint HTTP', [
            'endpoint' => $conversionEndpoint,
            'docx' => $docxPath
        ]);

        $startTime = microtime(true);

        try {
            // Crear cliente HTTP con Guzzle
            $client = new \GuzzleHttp\Client([
                'timeout' => 120, // 2 minutos timeout
                'verify' => false, // Deshabilitar verificación SSL si es necesario
            ]);

            // Preparar multipart/form-data con el archivo DOCX
            $response = $client->post($conversionEndpoint, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($docxPath, 'r'),
                        'filename' => basename($docxPath),
                    ]
                ]
            ]);

            $execTime = round(microtime(true) - $startTime, 2);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Endpoint de conversión retornó código ' . $response->getStatusCode());
            }

            // Guardar el PDF recibido
            $pdfContent = $response->getBody()->getContents();
            
            if (empty($pdfContent) || strlen($pdfContent) < 1024) {
                throw new \RuntimeException('El PDF recibido está vacío o es demasiado pequeño');
            }

            $expectedPdfName = pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            $expectedPdfPath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $expectedPdfName;
            
            file_put_contents($expectedPdfPath, $pdfContent);

            if (file_exists($expectedPdfPath) && @filesize($expectedPdfPath) > 1024) {
                Log::info('PDF generado exitosamente vía endpoint HTTP', [
                    'path' => $expectedPdfPath,
                    'size_kb' => round(filesize($expectedPdfPath)/1024, 2),
                    'time_seconds' => $execTime
                ]);
                return $expectedPdfPath;
            } else {
                throw new \RuntimeException('No se pudo guardar el PDF en el disco');
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $execTime = round(microtime(true) - $startTime, 2);
            Log::error('Error al conectar con endpoint de conversión', [
                'endpoint' => $conversionEndpoint,
                'error' => $e->getMessage(),
                'time_seconds' => $execTime
            ]);
            throw new \RuntimeException('Fallo en conversión HTTP: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $execTime = round(microtime(true) - $startTime, 2);
            Log::error('Error inesperado al convertir DOCX a PDF', [
                'error' => $e->getMessage(),
                'time_seconds' => $execTime
            ]);
            throw $e;
        }

        return null;
    }

    /**
     * Convertir DOCX a PDF usando Microsoft Word (COM Automation) en Windows.
     * Requiere: PHP COM habilitado y MS Word instalado en el host.
     */
    private function convertDocxToPdfWithMsWord(string $docxPath, string $outputPdfPath, ?string $title = null, ?string $companyName = null): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            throw new \RuntimeException('MS Word COM solo está disponible en Windows');
        }
        if (!file_exists($docxPath)) {
            throw new \RuntimeException("DOCX no existe: {$docxPath}");
        }
        if (!class_exists('COM')) {
            throw new \RuntimeException('Extensión COM de PHP no disponible');
        }

        Log::info('Intentando exportación a PDF con Microsoft Word COM', [
            'docx' => $docxPath,
            'pdf' => $outputPdfPath,
        ]);

        $word = null;
        $doc = null;
        try {
            $word = new \COM('Word.Application');
            $word->Visible = 0;
            // Desactivar alertas/modal
            $word->DisplayAlerts = 0;

            // Abrir documento
            // Parámetros: FileName, ConfirmConversions, ReadOnly, AddToRecentFiles, PasswordDocument, PasswordTemplate, Revert, WritePasswordDocument, WritePasswordTemplate, Format, Encoding, Visible, OpenAndRepair, DocumentDirection, NoEncodingDialog
            $doc = $word->Documents->Open($docxPath, false, false);

            // Actualizar propiedades opcionalmente para que el PDF no muestre "PHPWord"
            try {
                if ($title) {
                    $doc->BuiltInDocumentProperties('Title')->Value = $title;
                }
                if ($companyName) {
                    $doc->BuiltInDocumentProperties('Company')->Value = $companyName;
                }
            } catch (\Throwable $p) {
                // Ignorar si no se pueden establecer
            }

            $wdExportFormatPDF = 17; // WdExportFormat.wdExportFormatPDF
            // ExportAsFixedFormat(OutputFileName, ExportFormat, OpenAfterExport, OptimizeFor, Range, From, To, Item, IncludeDocProps, KeepIRM, CreateBookmarks, DocStructureTags, BitmapMissingFonts, UseISO19005_1)
            $doc->ExportAsFixedFormat($outputPdfPath, $wdExportFormatPDF, false);

            // Cerrar y salir
            $doc->Close(false);
            $doc = null;
            $word->Quit(false);
            $word = null;

            if (!file_exists($outputPdfPath) || (int)@filesize($outputPdfPath) <= 0) {
                throw new \RuntimeException('MS Word no generó el PDF esperado');
            }
        } catch (\Throwable $e) {
            // Intentar limpiar recursos si algo quedó abierto
            try { if ($doc) { $doc->Close(false); } } catch (\Throwable $x) {}
            try { if ($word) { $word->Quit(false); } } catch (\Throwable $x) {}
            $doc = null; $word = null;
            throw $e;
        }
    }

    /**
     * Eliminar imágenes WMF/EMF del DOCX para evitar errores "Invalid image" al cargar con PhpWord.
     * Crea una copia limpia del archivo si se modificó algo.
     *
     * @param string $docxPath
     * @return string Ruta (posiblemente nueva) del DOCX saneado.
     */
    private function sanitizeDocxImages(string $docxPath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return $docxPath;
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return $docxPath;
        }
        
        // Buscar imágenes WMF/EMF a eliminar
        $filesToRemove = [];
        $imageRels = []; // Guardar IDs de relaciones a eliminar
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) continue;
            $name = $stat['name'];
            
            if (preg_match('/word\/media\/(image\\d+)\.(wmf|emf)$/i', $name, $matches)) {
                $filesToRemove[] = $name;
                $imageRels[] = $matches[1]; // Guardar nombre base de la imagen (ej: image26)
            }
        }
        
        if (!count($filesToRemove)) {
            $zip->close();
            return $docxPath;
        }
        
        // Crear copia sanitizada
        $sanitizedPath = preg_replace('/\.docx$/', '_sanitized.docx', $docxPath);
        copy($docxPath, $sanitizedPath);
        $zip->close();
        
        // Reabrir y modificar
        if ($zip->open($sanitizedPath) !== true) {
            return $docxPath;
        }
        
        try {
            // 1. Eliminar archivos de imagen WMF/EMF del ZIP
            foreach ($filesToRemove as $f) {
                $zip->deleteName($f);
            }
            
            // 2. Limpiar [Content_Types].xml - eliminar referencias a WMF/EMF
            $contentTypesXml = $zip->getFromName('[Content_Types].xml');
            if ($contentTypesXml !== false) {
                $dom = new \DOMDocument();
                $dom->loadXML($contentTypesXml);
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
                
                // Eliminar Override entries para archivos WMF/EMF
                foreach ($filesToRemove as $file) {
                    $partName = '/' . $file;
                    $overrides = $xpath->query("//ct:Override[@PartName='$partName']");
                    foreach ($overrides as $override) {
                        $override->parentNode->removeChild($override);
                    }
                }
                
                $zip->addFromString('[Content_Types].xml', $dom->saveXML());
            }
            
            // 3. Limpiar document.xml - eliminar nodos <w:drawing> que referencian WMF/EMF
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml !== false) {
                $dom = new \DOMDocument();
                @$dom->loadXML($documentXml);
                $xpath = new \DOMXPath($dom);
                
                // Registrar namespaces comunes de Word
                $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
                $xpath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
                
                // Buscar y eliminar todos los w:drawing que contienen referencias a las imágenes eliminadas
                foreach ($imageRels as $imageName) {
                    // Buscar w:drawing que contengan referencias a esta imagen
                    $drawings = $xpath->query("//w:drawing[.//a:blip[contains(@r:embed, 'rId') or contains(@r:link, 'rId')]]");
                    
                    foreach ($drawings as $drawing) {
                        // Verificar si este drawing referencia la imagen eliminada buscando en su XML
                        $drawingXml = $dom->saveXML($drawing);
                        if (strpos($drawingXml, $imageName) !== false) {
                            // Eliminar el párrafo completo que contiene el drawing
                            $parent = $drawing->parentNode;
                            if ($parent && $parent->nodeName === 'w:r') {
                                // El drawing está dentro de un run, eliminar el run completo
                                $run = $parent;
                                $paragraph = $run->parentNode;
                                if ($paragraph) {
                                    $paragraph->removeChild($run);
                                    // Si el párrafo quedó vacío, eliminarlo también
                                    if (!$paragraph->hasChildNodes() || $paragraph->childNodes->length === 0) {
                                        if ($paragraph->parentNode) {
                                            $paragraph->parentNode->removeChild($paragraph);
                                        }
                                    }
                                }
                            } else {
                                // Eliminar el drawing directamente
                                $parent->removeChild($drawing);
                            }
                        }
                    }
                }
                
                $zip->addFromString('word/document.xml', $dom->saveXML());
            }
            
            // 4. Limpiar document.xml.rels - eliminar relaciones a archivos WMF/EMF
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            if ($relsXml !== false) {
                $dom = new \DOMDocument();
                @$dom->loadXML($relsXml);
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                
                foreach ($imageRels as $imageName) {
                    // Buscar relaciones que apuntan a archivos WMF/EMF con este nombre base
                    $relationships = $xpath->query("//r:Relationship[contains(@Target, '$imageName.wmf') or contains(@Target, '$imageName.emf') or contains(@Target, '$imageName')]");
                    foreach ($relationships as $rel) {
                        $parent = $rel->parentNode;
                        if ($parent) {
                            $parent->removeChild($rel);
                        }
                    }
                }
                
                $zip->addFromString('word/_rels/document.xml.rels', $dom->saveXML());
            }
            
            // 5. Limpiar también header/footer rels si existen
            for ($i = 1; $i <= 3; $i++) {
                foreach (['header', 'footer'] as $type) {
                    $headerFooterRels = "word/_rels/{$type}{$i}.xml.rels";
                    $relsXml = $zip->getFromName($headerFooterRels);
                    if ($relsXml !== false) {
                        $dom = new \DOMDocument();
                        @$dom->loadXML($relsXml);
                        $xpath = new \DOMXPath($dom);
                        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                        
                        $modified = false;
                        foreach ($imageRels as $imageName) {
                            $relationships = $xpath->query("//r:Relationship[contains(@Target, '$imageName.wmf') or contains(@Target, '$imageName.emf') or contains(@Target, '$imageName')]");
                            foreach ($relationships as $rel) {
                                $parent = $rel->parentNode;
                                if ($parent) {
                                    $parent->removeChild($rel);
                                    $modified = true;
                                }
                            }
                        }
                        
                        if ($modified) {
                            $zip->addFromString($headerFooterRels, $dom->saveXML());
                        }
                    }
                }
            }
            
            $zip->close();
            Log::warning('Sanitized DOCX: removed WMF/EMF images and all references', [
                'removed_files' => $filesToRemove,
                'image_rels' => $imageRels
            ]);
            return $sanitizedPath;
            
        } catch (\Throwable $e) {
            $zip->close();
            Log::error('Error sanitizing DOCX: ' . $e->getMessage());
            return $docxPath;
        }
    }

    /**
     * Inserta una tabla de 3 columnas al inicio del documento con info del reemplazo de anexo.
     * Layout: [Logo Izq] | [Nombre del Anexo] | [Logo Der + Código]
     * Debajo, una fila con versión/fecha y "Fecha subida". Luego, un salto y el contenido continúa.
     *
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     * @param array $companyData
     * @param array $annexHeaderData keys: name, code, uploaded_at
     * @return void
     */
    private function addAnnexReplacementTable(\PhpOffice\PhpWord\PhpWord $phpWord, array $companyData, array $annexHeaderData): void
    {
        $sections = $phpWord->getSections();
        if (!count($sections)) return;
        $section = $sections[0];

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'width' => 100 * 50,
        ];
        $table = $section->addTable($tableStyle);
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3));

        // Celda 1: Logo izquierdo
        $cellLeft = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3), ['valign' => 'center']);
        $this->addDocxLogo($cellLeft, $companyData['logo_izquierdo'] ?? null, 2.5);

        // Celda 2: Nombre del anexo
        $cellCenter = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(11), ['valign' => 'center']);
        $tr = $cellCenter->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $tr->addText(strtoupper($annexHeaderData['name'] ?? 'ANEXO'), ['bold' => true, 'size' => 14, 'name' => 'Arial']);

        // Celda 3: Logo derecho + Código
        $cellRight = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3.5), ['valign' => 'center']);
        $this->addDocxLogo($cellRight, $companyData['logo_derecho'] ?? null, 2);
        $cellRight->addTextBreak();
        $tr = $cellRight->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $tr->addText($annexHeaderData['code'] ?? '', ['bold' => true, 'size' => 10, 'name' => 'Arial']);

        // Segunda fila: versión/fecha/código y fecha subida
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5));
        $cellMetaLeft = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(8.5), ['valign' => 'top', 'gridSpan' => 2]);
        $tr = $cellMetaLeft->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $fecha = isset($companyData['fecha_inicio']) ? date('M-y', strtotime($companyData['fecha_inicio'])) : '';
        $tr->addText('Versión ' . ($companyData['version'] ?? '01') . ($fecha ? '      ' . $fecha : ''), ['size' => 9, 'name' => 'Arial']);

        $cellMetaRight = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(9), ['valign' => 'top']);
        if (!empty($annexHeaderData['uploaded_at'])) {
            $tr = $cellMetaRight->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $tr->addText('Fecha subida: ' . $annexHeaderData['uploaded_at'], ['size' => 9, 'name' => 'Arial']);
        }

        // Espacio y que siga el contenido del documento
        $section->addTextBreak(1);
    }

    private function addDocxLogo($cell, ?string $logoPath, float $widthCm = 2.5): void
    {
        if (!$logoPath) {
            $cell->addText('', ['size' => 1]);
            return;
        }
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
            }
            if (file_exists($fullPath)) {
                $cell->addImage($fullPath, [
                    'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel($widthCm),
                    'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel($widthCm * 0.8),
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                ]);
            }
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    /**
     * Genera una imagen PNG con la metadata del anexo (logos, nombre, código, versión/fecha, fecha subida).
     * Estructura: tabla 3 columnas (Logo izq | Título con fondo | Panel derecho con versión/logo/fecha/código)
     * Retorna la ruta absoluta del PNG temporal generado.
     */
    private function generateAnnexMetadataImage(array $companyData, array $annexHeaderData): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Dimensiones RECTANGULARES - más ancho que alto para mejor legibilidad
        $width = 2400; 
        $height = 800; // Altura reducida para formato rectangular
        $borderThick = 4;
        
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $goldBg = imagecolorallocate($im, 153, 102, 51); // Color dorado/marrón del título
        $navyBlue = imagecolorallocate($im, 0, 51, 102); // Azul marino para "Código"
        
        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $white);

        // === HELPERS ===
        $loadImage = function (?string $path) {
            if (!$path) return null;
            try {
                if (Storage::disk('public')->exists($path)) {
                    $full = Storage::disk('public')->path($path);
                } else {
                    $full = storage_path('app/public/' . ltrim($path, '/'));
                }
                if (!file_exists($full)) return null;
                $data = @file_get_contents($full);
                if ($data === false) return null;
                return @imagecreatefromstring($data) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        };

        $drawScaled = function ($dstIm, $srcIm, $dstX, $dstY, $maxW, $maxH) {
            if (!$srcIm) return;
            $sw = imagesx($srcIm);
            $sh = imagesy($srcIm);
            if ($sw <= 0 || $sh <= 0) return;
            $scale = min($maxW / $sw, $maxH / $sh);
            $tw = max(1, (int)round($sw * $scale));
            $th = max(1, (int)round($sh * $scale));
            imagecopyresampled($dstIm, $srcIm, $dstX + (int)(($maxW - $tw)/2), $dstY + (int)(($maxH - $th)/2), 0, 0, $tw, $th, $sw, $sh);
        };

        $drawTextCentered = function ($im, $text, $x, $y, $w, $font, $color) {
            $textW = imagefontwidth($font) * strlen($text);
            $textX = $x + max(0, (int)(($w - $textW) / 2));
            imagestring($im, $font, $textX, $y, $text, $color);
        };

        // Intentar cargar fuente TrueType para textos más grandes y legibles
        $fontPath = storage_path('fonts/arial.ttf');
        if (!file_exists($fontPath)) {
            // Fallback a fuente del sistema Windows
            $fontPath = 'C:/Windows/Fonts/arial.ttf';
        }
        $useTTF = file_exists($fontPath);

        $drawTextCenteredTTF = function ($im, $text, $x, $y, $w, $fontSize, $color, $fontPath) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $x + max(0, (int)(($w - $textW) / 2));
            imagettftext($im, $fontSize, 0, $textX, $y, $color, $fontPath, $text);
        };

        // === ESTRUCTURA: 3 COLUMNAS ===
        // Col1: Logo izquierdo (450px)
        // Col2: Título con fondo dorado (1280px)
        // Col3: Panel derecho info (670px)
        $col1W = 450;
        $col2W = 1280;
        $col3W = $width - $col1W - $col2W;
        
        $col1X = 0;
        $col2X = $col1W;
        $col3X = $col2X + $col2W;

        // === DIBUJAR BORDES DE TABLA ===
        // Borde exterior completo
        imagesetthickness($im, $borderThick);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $black);
        
        // Líneas verticales divisorias (col1|col2 y col2|col3)
        imageline($im, $col2X, 0, $col2X, $height - 1, $black);
        imageline($im, $col3X, 0, $col3X, $height - 1, $black);

        // === COLUMNA 1: LOGO IZQUIERDO ===
        $leftLogo = $loadImage($companyData['logo_izquierdo'] ?? null);
        if ($leftLogo) {
            $drawScaled($im, $leftLogo, $col1X + 20, 20, $col1W - 40, $height - 40);
            imagedestroy($leftLogo);
        }

        // === COLUMNA 2: TÍTULO CON FONDO DORADO ===
        imagefilledrectangle($im, $col2X + 1, 1, $col3X - 1, $height - 2, $goldBg);
        
        $title = strtoupper($annexHeaderData['name'] ?? 'ANEXO');
        if ($useTTF) {
            $titleFontSize = 42; // Ajustado para formato rectangular
            $titleY = (int)($height / 2 + 14); // Centrado verticalmente
            $drawTextCenteredTTF($im, $title, $col2X, $titleY, $col2W, $titleFontSize, $white, $fontPath);
        } else {
            $fontTitle = 5;
            $titleY = (int)($height / 2 - imagefontheight($fontTitle) / 2);
            $drawTextCentered($im, $title, $col2X, $titleY, $col2W, $fontTitle, $white);
        }

        // === COLUMNA 3: PANEL DERECHO (4 FILAS) ===
        // Dividir en 4 filas iguales para: Versión | Logo pequeño | Fecha | Código
        $rowHeight = (int)($height / 4); // 800/4 = 200px por fila
        
        // Dibujar líneas horizontales para separar filas en col3
        for ($i = 1; $i < 4; $i++) {
            $lineY = $i * $rowHeight;
            imageline($im, $col3X, $lineY, $width - 1, $lineY, $black);
        }
        
        // FILA 1: "Versión" + número
        $row1Y = 50; // Ajustado para formato rectangular
        $versionLabel = "Version";
        $versionValue = $companyData['version'] ?? '1';
        if ($useTTF) {
            $drawTextCenteredTTF($im, $versionLabel, $col3X, $row1Y + 20, $col3W, 22, $black, $fontPath);
            $drawTextCenteredTTF($im, $versionValue, $col3X, $row1Y + 80, $col3W, 32, $black, $fontPath);
        } else {
            $drawTextCentered($im, $versionLabel, $col3X, $row1Y, $col3W, 4, $black);
            $drawTextCentered($im, $versionValue, $col3X, $row1Y + 30, $col3W, 5, $black);
        }

        // FILA 2: Logo pequeño (derecho)
        $row2Y = $rowHeight;
        $rightLogo = $loadImage($companyData['logo_derecho'] ?? null);
        if ($rightLogo) {
            $drawScaled($im, $rightLogo, $col3X + 30, $row2Y + 20, $col3W - 60, $rowHeight - 40);
            imagedestroy($rightLogo);
        }

        // FILA 3: "Fecha" + fecha formateada
        $row3Y = $rowHeight * 2 + 50; // Ajustado para formato rectangular
        $fechaLabel = "Fecha";
        $fechaFmt = '';
        if (!empty($companyData['fecha_inicio'])) {
            $ts = strtotime($companyData['fecha_inicio']);
            if ($ts) { $fechaFmt = date('d/m/Y', $ts); }
        }
        if (empty($fechaFmt) && !empty($annexHeaderData['uploaded_at'])) {
            $fechaFmt = substr($annexHeaderData['uploaded_at'], 0, 10);
        }
        if ($useTTF) {
            $drawTextCenteredTTF($im, $fechaLabel, $col3X, $row3Y + 20, $col3W, 22, $black, $fontPath);
            $drawTextCenteredTTF($im, $fechaFmt, $col3X, $row3Y + 80, $col3W, 28, $black, $fontPath);
        } else {
            $drawTextCentered($im, $fechaLabel, $col3X, $row3Y + 5, $col3W, 4, $black);
            $drawTextCentered($im, $fechaFmt, $col3X, $row3Y + 35, $col3W, 3, $black);
        }

        // FILA 4: "Código" (fondo azul marino) + código del anexo
        $row4Y = $rowHeight * 3 + 50; // Ajustado para formato rectangular
        imagefilledrectangle($im, $col3X + 1, $row4Y - 50 + 1, $width - 2, $height - 2, $navyBlue);
        $codigoLabel = "Codigo";
        $codigoValue = $annexHeaderData['code'] ?? '';
        if ($useTTF) {
            $drawTextCenteredTTF($im, $codigoLabel, $col3X, $row4Y + 20, $col3W, 22, $white, $fontPath);
            $drawTextCenteredTTF($im, $codigoValue, $col3X, $row4Y + 80, $col3W, 28, $white, $fontPath);
        } else {
            $drawTextCentered($im, $codigoLabel, $col3X, $row4Y + 5, $col3W, 4, $white);
            $drawTextCentered($im, $codigoValue, $col3X, $row4Y + 35, $col3W, 3, $white);
        }

        imagesetthickness($im, 1);

        $outPath = $tempDir . '/' . uniqid('annex_meta_', true) . '.png';
        imagepng($im, $outPath);
        imagedestroy($im);

        Log::info('Imagen metadata generada con bordes de cuadrícula', ['path' => $outPath]);
        return $outPath;
    }

    /**
     * Combina verticalmente la imagen de metadata con las imágenes del anexo.
     * La metadata ocupará 50% del espacio vertical y el contenido 50%.
     * La imagen final tendrá ancho normalizado de 2400px para ocupar 100% del contenedor.
     * @param string $metadataImagePath Ruta a la imagen de metadata generada
     * @param array $annexImages Array de rutas a las imágenes del anexo
     * @return string Ruta al PNG combinado
     */
    private function combineImagesVertically(string $metadataImagePath, array $annexImages): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Ancho estándar para todas las imágenes combinadas (ocupará 100% del contenedor)
        $standardWidth = 2400;
        $spacing = 20;

        // Cargar imagen de metadata
        $metaImg = @imagecreatefrompng($metadataImagePath);
        if (!$metaImg) {
            throw new \Exception("No se pudo cargar imagen de metadata: {$metadataImagePath}");
        }

        $metaWidth = imagesx($metaImg);
        $metaHeight = imagesy($metaImg);

        // Cargar y escalar todas las imágenes del anexo al ancho estándar
        $scaledImages = [];
        $totalContentHeight = 0;

        foreach ($annexImages as $path) {
            $img = @imagecreatefromstring(file_get_contents($path));
            if (!$img) {
                Log::warning("No se pudo cargar imagen del anexo: {$path}");
                continue;
            }
            
            $origWidth = imagesx($img);
            $origHeight = imagesy($img);
            
            // Escalar al ancho estándar manteniendo proporciones
            $scaleFactor = $standardWidth / $origWidth;
            $newHeight = (int)($origHeight * $scaleFactor);
            
            $scaledImg = imagecreatetruecolor($standardWidth, $newHeight);
            $white = imagecolorallocate($scaledImg, 255, 255, 255);
            imagefill($scaledImg, 0, 0, $white);
            imagecopyresampled($scaledImg, $img, 0, 0, 0, 0, $standardWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($img);
            
            $scaledImages[] = [
                'resource' => $scaledImg,
                'width' => $standardWidth,
                'height' => $newHeight,
            ];
            $totalContentHeight += $newHeight;
        }

        if (empty($scaledImages)) {
            // Si no hay imágenes del anexo, retornar solo la metadata
            imagedestroy($metaImg);
            return $metadataImagePath;
        }

        // Imagen con ancho estándar 2400px y altura variable según contenido
        // Metadata se mantiene en su altura original (800px)
        // Contenido se escala al ancho estándar manteniendo proporciones
        
        // Escalar metadata al ancho estándar si es necesario
        if ($metaWidth > $standardWidth) {
            $metaScaleFactor = $standardWidth / $metaWidth;
            $newMetaWidth = $standardWidth;
            $newMetaHeight = (int)($metaHeight * $metaScaleFactor);
            
            $scaledMeta = imagecreatetruecolor($newMetaWidth, $newMetaHeight);
            $white = imagecolorallocate($scaledMeta, 255, 255, 255);
            imagefill($scaledMeta, 0, 0, $white);
            imagecopyresampled($scaledMeta, $metaImg, 0, 0, 0, 0, $newMetaWidth, $newMetaHeight, $metaWidth, $metaHeight);
            imagedestroy($metaImg);
            $metaImg = $scaledMeta;
            $metaWidth = $newMetaWidth;
            $metaHeight = $newMetaHeight;
        }
        
        // Calcular altura total: metadata + contenido (sin espaciado)
        $totalHeight = $metaHeight + $totalContentHeight;

        // Crear imagen combinada con ancho estándar y altura variable
        $combined = imagecreatetruecolor($standardWidth, $totalHeight);
        $white = imagecolorallocate($combined, 255, 255, 255);
        imagefilledrectangle($combined, 0, 0, $standardWidth - 1, $totalHeight - 1, $white);

        // Copiar metadata al inicio (centrada horizontalmente)
        $currentY = 0;
        $metaX = (int)(($standardWidth - $metaWidth) / 2);
        imagecopy($combined, $metaImg, $metaX, $currentY, 0, 0, $metaWidth, $metaHeight);
        $currentY += $metaHeight;
        imagedestroy($metaImg);

        // Copiar cada imagen del contenido (ya están escaladas al ancho estándar)
        foreach ($scaledImages as $imgData) {
            imagecopy($combined, $imgData['resource'], 0, $currentY, 0, 0, $imgData['width'], $imgData['height']);
            $currentY += $imgData['height'];
            imagedestroy($imgData['resource']);
        }

        // Guardar imagen combinada
        $outPath = $tempDir . '/' . uniqid('combined_annex_', true) . '.png';
        imagepng($combined, $outPath);
        imagedestroy($combined);

        Log::info('Imagen combinada con ancho completo (metadata + contenido)', [
            'path' => $outPath,
            'images_count' => count($scaledImages),
            'width' => $standardWidth,
            'height' => $totalHeight,
            'is_square' => ($standardWidth === $totalHeight),
            'metadata_height' => $metaHeight,
            'content_height' => $totalContentHeight,
            'metadata_percentage' => round(($metaHeight / $totalHeight) * 100, 1) . '%'
        ]);

        return $outPath;
    }

    /**
     * Crea una tabla con las imágenes apiladas verticalmente (una debajo de otra).
     * Se usa para insertar metadata PNG seguida de imágenes del anexo.
     * @param string[] $imagePaths
     * @return \PhpOffice\PhpWord\Element\Table
     */
    private function buildImageStackTable(array $imagePaths)
    {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'width' => 100 * 50,
        ];
        $table = new \PhpOffice\PhpWord\Element\Table($tableStyle);

        // Celda única por fila para cada imagen, ancho completo
        foreach ($imagePaths as $idx => $img) {
            $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1));
            $cell = $table->addCell(null, ['valign' => 'top']);
            if (file_exists($img)) {
                $cell->addImage($img, [
                    'width' => 500,
                    'ratio' => true,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                ]);
            } else {
                $cell->addText('(Imagen no encontrada)', ['name' => 'Arial', 'size' => 9]);
            }
        }

        return $table;
    }

    /**
     * Construye un bloque con una sola imagen a ancho completo (aprox. 16.5 cm).
     * Usamos una tabla de una celda para asegurar el 100% horizontal del contenido.
     * Generamos un ID único para evitar conflictos con imágenes existentes.
     */
    private function buildFullWidthImageBlock(string $imagePath, float $widthCm = 16.5)
    {
        $tableStyle = [
            'borderSize' => 0,
            'cellMargin' => 0,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'width' => 100 * 50, // 100%
        ];
        $table = new \PhpOffice\PhpWord\Element\Table($tableStyle);
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1));
        $cell = $table->addCell(null, ['valign' => 'top']);
        
        if (file_exists($imagePath)) {
            // Copiar imagen a un path temporal único para evitar conflictos de ID
            $tempDir = storage_path('app/temp');
            $uniquePath = $tempDir . '/' . uniqid('img_', true) . '_' . basename($imagePath);
            copy($imagePath, $uniquePath);
            
            $cell->addImage($uniquePath, [
                'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel($widthCm),
                'ratio' => true,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
            
            // Registrar para limpieza posterior
            Log::info('Imagen temporal única creada para inserción', ['path' => $uniquePath]);
        } else {
            $cell->addText('(Imagen no encontrada)', ['name' => 'Arial', 'size' => 10]);
        }
        return $table;
    }

    /**
     * Crea una copia única de una imagen para evitar conflictos de ID con imágenes existentes en la plantilla.
     * @param string $originalPath Ruta a la imagen original
     * @return string Ruta a la copia única
     */
    private function createUniqueImageCopy(string $originalPath): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $uniquePath = $tempDir . '/' . uniqid('img_', true) . '.' . $extension;
        copy($originalPath, $uniquePath);
        
        Log::info('Copia única de imagen creada', ['original' => basename($originalPath), 'copia' => basename($uniquePath)]);
        return $uniquePath;
    }

    /**
     * Renderiza texto plano como una imagen PNG.
     * @param string $text Texto a renderizar
     * @return string Ruta al PNG generado
     */
    private function renderTextAsImage(string $text): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Dimensiones CUADRADAS - mismo ancho y alto
        $width = 2400;
        $height = 2400; // Ahora es cuadrado
        $lineHeight = 45; // Aumentado para mejor legibilidad
        $padding = 60;
        $fontSize = 5; // Fuente GD
        
        // Intentar cargar fuente TrueType
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        $useTTF = file_exists($fontPath);
        $ttfSize = 24; // Aumentado de 18 a 24

        // Dividir texto en líneas (respetando saltos de línea y wrapping)
        $maxCharsPerLine = $useTTF ? 90 : 110; // Ajustado para texto más grande
        $lines = [];
        $textLines = explode("\n", $text);
        
        foreach ($textLines as $line) {
            if (empty($line)) {
                $lines[] = ''; // Línea vacía
                continue;
            }
            // Wrap largo
            $wrapped = wordwrap($line, $maxCharsPerLine, "\n", true);
            $lines = array_merge($lines, explode("\n", $wrapped));
        }

        // Limitar número de líneas para que quepa en el cuadrado
        $maxLines = (int)(($height - ($padding * 2)) / $lineHeight);
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[] = '...'; // Indicador de texto truncado
        }
        
        // Crear imagen cuadrada
        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $borderColor = imagecolorallocate($im, 200, 200, 200);
        
        imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $white);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);

        // Dibujar cada línea
        $y = $padding;
        foreach ($lines as $line) {
            if ($useTTF) {
                imagettftext($im, $ttfSize, 0, $padding, $y + $ttfSize, $black, $fontPath, $line);
            } else {
                imagestring($im, $fontSize, $padding, $y, $line, $black);
            }
            $y += $lineHeight;
        }

        $outPath = $tempDir . '/' . uniqid('text_render_', true) . '.png';
        imagepng($im, $outPath);
        imagedestroy($im);

        Log::info('Texto renderizado como imagen cuadrada', ['path' => $outPath, 'lines' => count($lines)]);
        return $outPath;
    }

    /**
     * Renderiza una tabla de datos como imagen PNG
     * 
     * @param array $tableData Array de filas con columnas
     * @param array $columns Nombres de las columnas
     * @param string $headerColor Color hex de la cabecera (ej: '#153366')
     * @return string Path a la imagen temporal generada
     */
    private function renderTableAsImage(array $tableData, array $columns, string $headerColor = '#153366'): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Configuración - DIMENSIONES CUADRADAS
        $totalWidth = 2400;
        $totalHeight = 2400; // Ahora es cuadrado
        
        $cellPadding = 20;
        $cellHeight = 60; // Aumentado de 40 a 60
        $headerHeight = 70; // Aumentado de 50 a 70
        $fontSize = 5;
        
        // Intentar cargar fuente TrueType
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        $useTTF = file_exists($fontPath);
        $ttfSizeHeader = 28; // Aumentado de 20 a 28
        $ttfSizeCell = 24; // Aumentado de 16 a 24

        // Calcular ancho de columnas (distribuir equitativamente)
        $colCount = count($columns);
        $colWidth = $colCount > 0 ? floor($totalWidth / $colCount) : $totalWidth;

        // Limitar filas para que quepan en el formato cuadrado
        $rowCount = count($tableData);
        $maxRows = (int)(($totalHeight - $headerHeight - 20) / $cellHeight); // -20 para padding
        if ($rowCount > $maxRows) {
            $tableData = array_slice($tableData, 0, $maxRows);
            $rowCount = count($tableData);
        }

        // Crear imagen cuadrada
        $im = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $borderColor = imagecolorallocate($im, 200, 200, 200);
        $textDark = imagecolorallocate($im, 30, 30, 30);
        $textWhite = imagecolorallocate($im, 255, 255, 255);
        
        // Parsear color de cabecera
        $headerRGB = $this->hexToRgb($headerColor);
        $headerBg = imagecolorallocate($im, $headerRGB['r'], $headerRGB['g'], $headerRGB['b']);
        
        // Fondo blanco
        imagefilledrectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $white);

        // Dibujar cabecera
        imagefilledrectangle($im, 0, 0, $totalWidth - 1, $headerHeight, $headerBg);
        
        $x = 0;
        foreach ($columns as $idx => $col) {
            // Borde vertical entre columnas
            if ($idx > 0) {
                imageline($im, $x, 0, $x, $headerHeight, $borderColor);
            }
            
            // Texto de la cabecera
            $textX = $x + $cellPadding;
            $textY = $useTTF ? ($headerHeight / 2) + 10 : ($headerHeight / 2) - 10;
            
            if ($useTTF) {
                imagettftext($im, $ttfSizeHeader, 0, $textX, $textY, $textWhite, $fontPath, $col);
            } else {
                imagestring($im, $fontSize, $textX, $textY, $col, $textWhite);
            }
            
            $x += $colWidth;
        }

        // Línea debajo de la cabecera
        imageline($im, 0, $headerHeight, $totalWidth, $headerHeight, $borderColor);

        // Dibujar filas de datos
        $y = $headerHeight;
        foreach ($tableData as $rowIdx => $row) {
            $x = 0;
            $y += $cellHeight;
            
            // Línea horizontal entre filas
            imageline($im, 0, $y, $totalWidth, $y, $borderColor);
            
            foreach ($columns as $idx => $col) {
                // Borde vertical
                if ($idx > 0) {
                    imageline($im, $x, $headerHeight, $x, $y, $borderColor);
                }
                
                // Texto de la celda
                $cellValue = $row[$col] ?? '-';
                // Truncar si es muy largo
                $maxChars = $useTTF ? 40 : 50; // Aumentado de 30/40 a 40/50
                if (strlen($cellValue) > $maxChars) {
                    $cellValue = substr($cellValue, 0, $maxChars - 3) . '...';
                }
                
                $textX = $x + $cellPadding;
                $textY = $useTTF ? ($y - $cellHeight / 2) + 9 : ($y - $cellHeight / 2) - 10; // Ajustado para nuevo tamaño
                
                if ($useTTF) {
                    imagettftext($im, $ttfSizeCell, 0, $textX, $textY, $textDark, $fontPath, $cellValue);
                } else {
                    imagestring($im, $fontSize, $textX, $textY, $cellValue, $textDark);
                }
                
                $x += $colWidth;
            }
        }

        // Borde exterior
        imagerectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $borderColor);

        $outPath = $tempDir . '/' . uniqid('table_render_', true) . '.png';
        imagepng($im, $outPath);
        imagedestroy($im);

        Log::info('Tabla renderizada como imagen cuadrada', ['path' => $outPath, 'rows' => $rowCount, 'cols' => $colCount, 'dimensions' => '2400x2400']);
        return $outPath;
    }

    /**
     * Convierte color hex a RGB
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Inserta/actualiza docProps/core.xml dentro del DOCX para fijar título y autor.
     * Esto permite que LibreOffice genere PDF con metadatos correctos (título != "PHPWord").
     */
    private function setDocxCoreProperties(string $docxPath, string $title, ?string $creator = null): void
    {
        if (!class_exists(\ZipArchive::class)) return;
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) return;
        try {
            $coreXmlName = 'docProps/core.xml';
            $xml = $zip->getFromName($coreXmlName);
            if ($xml === false) {
                // Crear estructura mínima si no existe
                $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
                    .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
                    .'xmlns:dcterms="http://purl.org/dc/terms/" '
                    .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
                    .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                    .'<dc:title></dc:title>'
                    .'<dc:creator></dc:creator>'
                    .'<cp:lastModifiedBy></cp:lastModifiedBy>'
                    .'</cp:coreProperties>';
            }
            $dom = new \DOMDocument();
            @$dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
            $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $ensureNode = function(string $query, string $fallbackName) use ($dom, $xpath) {
                $nodes = $xpath->query($query);
                if ($nodes && $nodes->length > 0) return $nodes->item(0);
                // Crear si no existe
                $parts = explode(':', $fallbackName);
                if (count($parts) === 2) {
                    $ns = $parts[0] === 'dc' ? 'http://purl.org/dc/elements/1.1/' : 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties';
                    $el = $dom->createElementNS($ns, $fallbackName);
                } else {
                    $el = $dom->createElement($fallbackName);
                }
                $root = $dom->documentElement ?: $dom->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/package/2006/metadata/core-properties','cp:coreProperties'));
                $root->appendChild($el);
                return $el;
            };

            $titleNode = $ensureNode('//dc:title', 'dc:title');
            while ($titleNode->firstChild) { $titleNode->removeChild($titleNode->firstChild); }
            $titleNode->appendChild($dom->createTextNode($title));

            if ($creator) {
                $creatorNode = $ensureNode('//dc:creator', 'dc:creator');
                while ($creatorNode->firstChild) { $creatorNode->removeChild($creatorNode->firstChild); }
                $creatorNode->appendChild($dom->createTextNode($creator));

                $lastByNode = $ensureNode('//cp:lastModifiedBy', 'cp:lastModifiedBy');
                while ($lastByNode->firstChild) { $lastByNode->removeChild($lastByNode->firstChild); }
                $lastByNode->appendChild($dom->createTextNode($creator));
            }

            $zip->addFromString($coreXmlName, $dom->saveXML());
        } finally {
            $zip->close();
        }
    }

    /**
     * Genera una imagen cuadrada (2400x2400) que combina metadata + tabla
     * La metadata ocupa la parte superior y la tabla el resto del espacio
     */
    private function generateTableWithMetadataImage(
        array $tableData,
        array $columns,
        string $headerColor,
        array $annexMetadata,
        array $companyData
    ): string {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Dimensiones cuadradas
        $totalWidth = 2400;
        $totalHeight = 2400;
        
        // Dividir espacio: metadata arriba (450px) + tabla abajo (1950px)
        $metadataHeight = 450;
        $tableHeight = $totalHeight - $metadataHeight;
        
        // Crear imagen cuadrada
        $im = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $goldBg = imagecolorallocate($im, 153, 102, 51);
        $navyBlue = imagecolorallocate($im, 0, 51, 102);
        $borderColor = imagecolorallocate($im, 0, 0, 0);
        $textDark = imagecolorallocate($im, 30, 30, 30);
        
        // Fondo blanco para toda la imagen
        imagefilledrectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $white);
        
        // === SECCIÓN METADATA (superior - 450px) ===
        $borderThick = 4;
        
        // Estructura de 3 columnas igual que generateAnnexMetadataImage
        $col1W = 450;  // Logo izquierdo
        $col2W = 1280; // Título con fondo dorado
        $col3W = $totalWidth - $col1W - $col2W; // Panel derecho (670px)
        
        $col1X = 0;
        $col2X = $col1W;
        $col3X = $col2X + $col2W;
        
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        $useTTF = file_exists($fontPath);
        
        // Helper para cargar imágenes
        $loadImage = function (?string $path) {
            if (!$path) return null;
            try {
                if (Storage::disk('public')->exists($path)) {
                    $full = Storage::disk('public')->path($path);
                } else {
                    $full = storage_path('app/public/' . ltrim($path, '/'));
                }
                if (!file_exists($full)) return null;
                $data = @file_get_contents($full);
                if ($data === false) return null;
                return @imagecreatefromstring($data) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        };
        
        // Helper para dibujar imagen escalada
        $drawScaled = function ($dstIm, $srcIm, $dstX, $dstY, $maxW, $maxH) {
            if (!$srcIm) return;
            $sw = imagesx($srcIm);
            $sh = imagesy($srcIm);
            if ($sw <= 0 || $sh <= 0) return;
            $scale = min($maxW / $sw, $maxH / $sh);
            $tw = max(1, (int)round($sw * $scale));
            $th = max(1, (int)round($sh * $scale));
            imagecopyresampled($dstIm, $srcIm, $dstX + (int)(($maxW - $tw)/2), $dstY + (int)(($maxH - $th)/2), 0, 0, $tw, $th, $sw, $sh);
        };
        
        // Borde superior de metadata
        imagesetthickness($im, $borderThick);
        imagerectangle($im, 0, 0, $totalWidth - 1, $metadataHeight - 1, $black);
        
        // Líneas verticales divisorias
        imageline($im, $col2X, 0, $col2X, $metadataHeight - 1, $black);
        imageline($im, $col3X, 0, $col3X, $metadataHeight - 1, $black);
        
        // COLUMNA 1: Logo izquierdo
        $leftLogo = $loadImage($companyData['logo_izquierdo'] ?? null);
        if ($leftLogo) {
            $drawScaled($im, $leftLogo, $col1X + 20, 20, $col1W - 40, $metadataHeight - 40);
            imagedestroy($leftLogo);
        }
        
        // COLUMNA 2: Título con fondo dorado
        imagefilledrectangle($im, $col2X + 1, 1, $col3X - 1, $metadataHeight - 2, $goldBg);
        
        if ($useTTF) {
            $title = strtoupper($annexMetadata['nombre']);
            $titleFontSize = 48;
            $titleY = (int)($metadataHeight / 2 + 18);
            $bbox = imagettfbbox($titleFontSize, 0, $fontPath, $title);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col2X + max(0, (int)(($col2W - $textW) / 2));
            imagettftext($im, $titleFontSize, 0, $textX, $titleY, $white, $fontPath, $title);
        }
        
        // COLUMNA 3: Panel derecho con 4 filas
        $rowHeight = (int)($metadataHeight / 4);
        
        // Líneas horizontales entre filas
        for ($i = 1; $i < 4; $i++) {
            $lineY = $i * $rowHeight;
            imageline($im, $col3X, $lineY, $totalWidth - 1, $lineY, $black);
        }
        
        if ($useTTF) {
            // FILA 1: Versión (tamaños reducidos 10%: 28->25, 36->32)
            $row1Y = 50;
            $versionLabel = "Version";
            $versionValue = $companyData['version'] ?? '1';
            $bbox = imagettfbbox(25, 0, $fontPath, $versionLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row1Y + 28, $black, $fontPath, $versionLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $versionValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row1Y + 70, $black, $fontPath, $versionValue);
            
            // FILA 2: Logo derecho pequeño
            $row2Y = $rowHeight;
            $rightLogo = $loadImage($companyData['logo_derecho'] ?? null);
            if ($rightLogo) {
                $drawScaled($im, $rightLogo, $col3X + 30, $row2Y + 20, $col3W - 60, $rowHeight - 40);
                imagedestroy($rightLogo);
            }
            
            // FILA 3: Fecha (tamaños reducidos 10%: 28->25, 36->32)
            $row3Y = $rowHeight * 2 + 50;
            $fechaLabel = "Fecha";
            $fechaValue = $annexMetadata['uploaded_at'] ?? date('d/m/Y');
            $bbox = imagettfbbox(25, 0, $fontPath, $fechaLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row3Y + 28, $black, $fontPath, $fechaLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $fechaValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row3Y + 70, $black, $fontPath, $fechaValue);
            
            // FILA 4: Código (tamaños reducidos 10%: 28->25, 36->32)
            $row4Y = $rowHeight * 3 + 50;
            $codigoLabel = "Codigo";
            $codigoValue = $annexMetadata['codigo'] ?? 'N/A';
            $bbox = imagettfbbox(25, 0, $fontPath, $codigoLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row4Y + 28, $navyBlue, $fontPath, $codigoLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $codigoValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row4Y + 70, $black, $fontPath, $codigoValue);
        }
        
        // === SECCIÓN TABLA (inferior) ===
        $tableStartY = $metadataHeight;
        
        $cellPadding = 20;
        $cellHeight = 60;
        $headerHeight = 70;
        $ttfSizeHeader = 28;
        $ttfSizeCell = 24;
        
        // Calcular ancho de columnas
        $colCount = count($columns);
        $colWidth = $colCount > 0 ? floor($totalWidth / $colCount) : $totalWidth;
        
        // Limitar filas para que quepan en el espacio disponible
        $rowCount = count($tableData);
        $maxRows = (int)(($tableHeight - $headerHeight - 40) / $cellHeight);
        if ($rowCount > $maxRows) {
            $tableData = array_slice($tableData, 0, $maxRows);
            $rowCount = count($tableData);
        }
        
        // Parsear color de cabecera de tabla
        $headerRGB = $this->hexToRgb($headerColor);
        $headerBg = imagecolorallocate($im, $headerRGB['r'], $headerRGB['g'], $headerRGB['b']);
        $textWhite = imagecolorallocate($im, 255, 255, 255);
        
        // Dibujar cabecera de tabla
        imagefilledrectangle($im, 0, $tableStartY, $totalWidth - 1, $tableStartY + $headerHeight, $headerBg);
        
        $x = 0;
        foreach ($columns as $idx => $col) {
            if ($idx > 0) {
                imageline($im, $x, $tableStartY, $x, $tableStartY + $headerHeight, $borderColor);
            }
            
            $textX = $x + $cellPadding;
            $textY = $tableStartY + ($headerHeight / 2) + 10;
            
            if ($useTTF) {
                imagettftext($im, $ttfSizeHeader, 0, $textX, $textY, $textWhite, $fontPath, $col);
            }
            
            $x += $colWidth;
        }
        
        // Línea debajo de la cabecera
        imageline($im, 0, $tableStartY + $headerHeight, $totalWidth, $tableStartY + $headerHeight, $borderColor);
        
        // Dibujar filas de datos
        $y = $tableStartY + $headerHeight;
        foreach ($tableData as $row) {
            $x = 0;
            $y += $cellHeight;
            
            imageline($im, 0, $y, $totalWidth, $y, $borderColor);
            
            foreach ($columns as $idx => $col) {
                if ($idx > 0) {
                    imageline($im, $x, $tableStartY + $headerHeight, $x, $y, $borderColor);
                }
                
                $cellValue = $row[$col] ?? '-';
                $maxChars = 40;
                if (strlen($cellValue) > $maxChars) {
                    $cellValue = substr($cellValue, 0, $maxChars - 3) . '...';
                }
                
                $textX = $x + $cellPadding;
                $textY = ($y - $cellHeight / 2) + 9;
                
                if ($useTTF) {
                    imagettftext($im, $ttfSizeCell, 0, $textX, $textY, $textDark, $fontPath, $cellValue);
                }
                
                $x += $colWidth;
            }
        }
        
        // Borde exterior
        imagerectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $borderColor);
        
        $outPath = $tempDir . '/' . uniqid('table_complete_', true) . '.png';
        imagepng($im, $outPath);
        imagedestroy($im);
        
        Log::info('Imagen cuadrada completa (metadata + tabla) generada', [
            'path' => $outPath,
            'rows' => $rowCount,
            'cols' => $colCount,
            'dimensions' => '2400x2400'
        ]);
        
        return $outPath;
    }

    /**
     * Genera una imagen cuadrada (2400x2400) que combina metadata + texto
     * La metadata ocupa la parte superior y el texto el resto del espacio
     */
    private function generateTextWithMetadataImage(
        string $textContent,
        array $annexMetadata,
        array $companyData,
        int $textFontSize = 20
    ): string {
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Dimensiones cuadradas
        $totalWidth = 2400;
        $totalHeight = 2400;
        
        // Dividir espacio: metadata arriba (450px) + texto abajo (1950px)
        $metadataHeight = 450;
        $textAreaHeight = $totalHeight - $metadataHeight;
        
        // Crear imagen cuadrada
        $im = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $goldBg = imagecolorallocate($im, 153, 102, 51);
        $navyBlue = imagecolorallocate($im, 0, 51, 102);
        $borderColor = imagecolorallocate($im, 0, 0, 0);
        $textDark = imagecolorallocate($im, 30, 30, 30);
        
        // Fondo blanco para toda la imagen
        imagefilledrectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $white);
        
        // === SECCIÓN METADATA (superior - 450px) ===
        $borderThick = 4;
        
        // Estructura de 3 columnas igual que generateAnnexMetadataImage
        $col1W = 450;  // Logo izquierdo
        $col2W = 1280; // Título con fondo dorado
        $col3W = $totalWidth - $col1W - $col2W; // Panel derecho (670px)
        
        $col1X = 0;
        $col2X = $col1W;
        $col3X = $col2X + $col2W;
        
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        $useTTF = file_exists($fontPath);
        
        // Helper para cargar imágenes
        $loadImage = function (?string $path) {
            if (!$path) return null;
            try {
                if (Storage::disk('public')->exists($path)) {
                    $full = Storage::disk('public')->path($path);
                } else {
                    $full = storage_path('app/public/' . ltrim($path, '/'));
                }
                if (!file_exists($full)) return null;
                $data = @file_get_contents($full);
                if ($data === false) return null;
                return @imagecreatefromstring($data) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        };
        
        // Helper para dibujar imagen escalada
        $drawScaled = function ($dstIm, $srcIm, $dstX, $dstY, $maxW, $maxH) {
            if (!$srcIm) return;
            $sw = imagesx($srcIm);
            $sh = imagesy($srcIm);
            if ($sw <= 0 || $sh <= 0) return;
            $scale = min($maxW / $sw, $maxH / $sh);
            $tw = max(1, (int)round($sw * $scale));
            $th = max(1, (int)round($sh * $scale));
            imagecopyresampled($dstIm, $srcIm, $dstX + (int)(($maxW - $tw)/2), $dstY + (int)(($maxH - $th)/2), 0, 0, $tw, $th, $sw, $sh);
        };
        
        // Borde superior de metadata
        imagesetthickness($im, $borderThick);
        imagerectangle($im, 0, 0, $totalWidth - 1, $metadataHeight - 1, $black);
        
        // Líneas verticales divisorias
        imageline($im, $col2X, 0, $col2X, $metadataHeight - 1, $black);
        imageline($im, $col3X, 0, $col3X, $metadataHeight - 1, $black);
        
        // COLUMNA 1: Logo izquierdo
        $leftLogo = $loadImage($companyData['logo_izquierdo'] ?? null);
        if ($leftLogo) {
            $drawScaled($im, $leftLogo, $col1X + 20, 20, $col1W - 40, $metadataHeight - 40);
            imagedestroy($leftLogo);
        }
        
        // COLUMNA 2: Título con fondo dorado
        imagefilledrectangle($im, $col2X + 1, 1, $col3X - 1, $metadataHeight - 2, $goldBg);
        
        if ($useTTF) {
            $title = strtoupper($annexMetadata['nombre']);
            $titleFontSize = 48;
            $titleY = (int)($metadataHeight / 2 + 18);
            $bbox = imagettfbbox($titleFontSize, 0, $fontPath, $title);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col2X + max(0, (int)(($col2W - $textW) / 2));
            imagettftext($im, $titleFontSize, 0, $textX, $titleY, $white, $fontPath, $title);
        }
        
        // COLUMNA 3: Panel derecho con 4 filas
        $rowHeight = (int)($metadataHeight / 4);
        
        // Líneas horizontales entre filas
        for ($i = 1; $i < 4; $i++) {
            $lineY = $i * $rowHeight;
            imageline($im, $col3X, $lineY, $totalWidth - 1, $lineY, $black);
        }
        
        if ($useTTF) {
            // FILA 1: Versión (tamaños reducidos 10%: 28->25, 36->32)
            $row1Y = 50;
            $versionLabel = "Version";
            $versionValue = $companyData['version'] ?? '1';
            $bbox = imagettfbbox(25, 0, $fontPath, $versionLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row1Y + 28, $black, $fontPath, $versionLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $versionValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row1Y + 70, $black, $fontPath, $versionValue);
            
            // FILA 2: Logo derecho pequeño
            $row2Y = $rowHeight;
            $rightLogo = $loadImage($companyData['logo_derecho'] ?? null);
            if ($rightLogo) {
                $drawScaled($im, $rightLogo, $col3X + 30, $row2Y + 20, $col3W - 60, $rowHeight - 40);
                imagedestroy($rightLogo);
            }
            
            // FILA 3: Fecha (tamaños reducidos 10%: 28->25, 36->32)
            $row3Y = $rowHeight * 2 + 50;
            $fechaLabel = "Fecha";
            $fechaValue = $annexMetadata['uploaded_at'] ?? date('d/m/Y');
            $bbox = imagettfbbox(25, 0, $fontPath, $fechaLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row3Y + 28, $black, $fontPath, $fechaLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $fechaValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row3Y + 70, $black, $fontPath, $fechaValue);
            
            // FILA 4: Código (tamaños reducidos 10%: 28->25, 36->32)
            $row4Y = $rowHeight * 3 + 50;
            $codigoLabel = "Codigo";
            $codigoValue = $annexMetadata['codigo'] ?? 'N/A';
            $bbox = imagettfbbox(25, 0, $fontPath, $codigoLabel);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 25, 0, $textX, $row4Y + 28, $navyBlue, $fontPath, $codigoLabel);
            
            $bbox = imagettfbbox(32, 0, $fontPath, $codigoValue);
            $textW = abs($bbox[4] - $bbox[0]);
            $textX = $col3X + max(0, (int)(($col3W - $textW) / 2));
            imagettftext($im, 32, 0, $textX, $row4Y + 70, $black, $fontPath, $codigoValue);
        }
        
        // === SECCIÓN TEXTO (inferior) ===
        $textStartY = $metadataHeight;
        
        // Formato APA: Márgenes de 1 pulgada (96 píxeles a 96 DPI, pero escalado a 2400px de ancho)
        // 1 pulgada = 2.54 cm, en imagen de 2400px (equivalente a ~21cm de ancho real) = ~110px por pulgada
        $marginLeft = 110;   // Margen izquierdo APA
        $marginRight = 110;  // Margen derecho APA
        $marginTop = 60;     // Margen superior del área de texto
        $marginBottom = 60;  // Margen inferior
        
        $textWidth = $totalWidth - $marginLeft - $marginRight; // Ancho del texto
        
        // Formato APA: Times New Roman, tamaño dinámico según parámetro
        $fontPathTimes = 'C:/Windows/Fonts/times.ttf';
        if (!file_exists($fontPathTimes)) {
            $fontPathTimes = 'C:/Windows/Fonts/Times.ttf'; // Algunas versiones
        }
        if (!file_exists($fontPathTimes)) {
            $fontPathTimes = 'C:/Windows/Fonts/timesbd.ttf'; // Fallback
        }
        $useTimesFont = file_exists($fontPathTimes);
        
        // Si no se encuentra Times, usar Arial como fallback
        if (!$useTimesFont) {
            $fontPathTimes = $fontPath; // Arial como fallback
        }
        
        $apaTtfSize = $textFontSize; // Usar tamaño de fuente proporcionado (20pt sin metadata, 30pt con metadata)
        $apaLineHeight = $textFontSize * 2; // Doble espacio proporcional al tamaño de fuente
        
        // Limpiar HTML del contenido de texto
        $textContent = strip_tags($textContent);
        $textContent = html_entity_decode($textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $textContent = trim($textContent);
        
        // Dividir texto en párrafos (respetando saltos de línea originales)
        $paragraphs = preg_split('/\n\s*\n/', $textContent); // Párrafos separados por líneas en blanco
        if (empty($paragraphs)) {
            $paragraphs = [$textContent];
        }
        
        $lines = [];
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // Dividir párrafo en líneas que quepan en el ancho disponible (justificado)
            $words = preg_split('/\s+/', $paragraph);
            $currentLine = '';
            
            foreach ($words as $word) {
                $testLine = empty($currentLine) ? $word : $currentLine . ' ' . $word;
                
                // Medir ancho del texto con TTF
                if ($useTTF) {
                    $bbox = imagettfbbox($apaTtfSize, 0, $fontPathTimes, $testLine);
                    $lineWidth = abs($bbox[4] - $bbox[0]);
                } else {
                    $lineWidth = strlen($testLine) * 7; // Estimación
                }
                
                if ($lineWidth > $textWidth && !empty($currentLine)) {
                    // Línea completa, guardarla
                    $lines[] = ['text' => $currentLine, 'justify' => true];
                    $currentLine = $word;
                } else {
                    $currentLine = $testLine;
                }
            }
            
            // Agregar última línea del párrafo (sin justificar, alineada a la izquierda)
            if (!empty($currentLine)) {
                $lines[] = ['text' => $currentLine, 'justify' => false];
            }
            
            // Espacio entre párrafos (doble espacio)
            $lines[] = ['text' => '', 'justify' => false];
        }
        
        // Limitar líneas para que quepan en el espacio disponible
        $availableHeight = $textAreaHeight - $marginTop - $marginBottom;
        $maxLines = (int)($availableHeight / $apaLineHeight);
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines - 1);
            $lines[] = ['text' => '...', 'justify' => false];
        }
        
        // Dibujar texto línea por línea con justificación
        $currentY = $textStartY + $marginTop + $apaLineHeight;
        foreach ($lines as $lineData) {
            $lineText = $lineData['text'];
            $shouldJustify = $lineData['justify'];
            
            if (empty($lineText)) {
                // Línea vacía, solo avanzar
                $currentY += $apaLineHeight;
                continue;
            }
            
            if ($useTimesFont) {
                if ($shouldJustify) {
                    // Justificar: distribuir palabras uniformemente
                    $words = preg_split('/\s+/', $lineText);
                    if (count($words) > 1) {
                        // Calcular espacio total que ocupan las palabras
                        $totalWordWidth = 0;
                        $wordWidths = [];
                        foreach ($words as $word) {
                            $bbox = imagettfbbox($apaTtfSize, 0, $fontPathTimes, $word);
                            $wordWidth = abs($bbox[4] - $bbox[0]);
                            $wordWidths[] = $wordWidth;
                            $totalWordWidth += $wordWidth;
                        }
                        
                        // Espacio disponible entre palabras
                        $remainingSpace = $textWidth - $totalWordWidth;
                        $spacePerGap = $remainingSpace / (count($words) - 1);
                        
                        // Dibujar palabras con espaciado justificado
                        $currentX = $marginLeft;
                        foreach ($words as $idx => $word) {
                            imagettftext($im, $apaTtfSize, 0, (int)$currentX, $currentY, $textDark, $fontPathTimes, $word);
                            $currentX += $wordWidths[$idx] + $spacePerGap;
                        }
                    } else {
                        // Una sola palabra, alinear a la izquierda
                        imagettftext($im, $apaTtfSize, 0, $marginLeft, $currentY, $textDark, $fontPathTimes, $lineText);
                    }
                } else {
                    // Sin justificar, alinear a la izquierda
                    imagettftext($im, $apaTtfSize, 0, $marginLeft, $currentY, $textDark, $fontPathTimes, $lineText);
                }
            }
            
            $currentY += $apaLineHeight;
        }
        
        // Borde exterior
        imagerectangle($im, 0, 0, $totalWidth - 1, $totalHeight - 1, $borderColor);
        
        $outPath = $tempDir . '/' . uniqid('text_complete_', true) . '.png';
        imagepng($im, $outPath);
        imagedestroy($im);
        
        Log::info('Imagen cuadrada completa (metadata + texto) generada', [
            'path' => $outPath,
            'lines' => count($lines),
            'dimensions' => '2400x2400'
        ]);
        
        return $outPath;
    }

    /**
     * Construye una tabla compleja para insertar en el placeholder del anexo:
     *  - Fila 1: logos y nombre del anexo (3 columnas)
     *  - Fila 2: versión/fecha (izq, 2 cols) y fecha subida (der)
     *  - Fila 3: contenido del anexo (celda que abarca 3 columnas)
     *
     * @param array $companyData
     * @param array $annexHeaderData name, code, uploaded_at
     * @param string|null $textContent
     * @param string|null $imagePath
     * @return \PhpOffice\PhpWord\Element\Table
     */
    private function buildAnnexComplexTable(array $companyData, array $annexHeaderData, ?string $textContent, ?string $imagePath)
    {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            'width' => 100 * 50,
        ];
        $table = new \PhpOffice\PhpWord\Element\Table($tableStyle);
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3));

        // Celda 1: Logo izquierdo
        $cellLeft = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3), ['valign' => 'center']);
        $this->addDocxLogo($cellLeft, $companyData['logo_izquierdo'] ?? null, 2.5);

        // Celda 2: Nombre del anexo
        $cellCenter = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(11), ['valign' => 'center']);
        $tr = $cellCenter->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $tr->addText(strtoupper($annexHeaderData['name'] ?? 'ANEXO'), ['bold' => true, 'size' => 14, 'name' => 'Arial']);

        // Celda 3: Logo derecho + Código
        $cellRight = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(3.5), ['valign' => 'center']);
        $this->addDocxLogo($cellRight, $companyData['logo_derecho'] ?? null, 2);
        $cellRight->addTextBreak();
        $tr = $cellRight->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        if (!empty($annexHeaderData['code'])) {
            $tr->addText($annexHeaderData['code'], ['bold' => true, 'size' => 10, 'name' => 'Arial']);
        }

        // Segunda fila: versión/fecha + fecha subida
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5));
        $cellMetaLeft = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(8.5), ['valign' => 'top', 'gridSpan' => 2]);
        $tr = $cellMetaLeft->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $fecha = isset($companyData['fecha_inicio']) ? date('M-y', strtotime($companyData['fecha_inicio'])) : '';
        $tr->addText('Versión ' . ($companyData['version'] ?? '01') . ($fecha ? '      ' . $fecha : ''), ['size' => 9, 'name' => 'Arial']);

        $cellMetaRight = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(9), ['valign' => 'top']);
        if (!empty($annexHeaderData['uploaded_at'])) {
            $tr = $cellMetaRight->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $tr->addText('Fecha subida: ' . $annexHeaderData['uploaded_at'], ['size' => 9, 'name' => 'Arial']);
        }

        // Fila de contenido
        $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1));
        $contentCell = $table->addCell(null, ['gridSpan' => 3, 'valign' => 'top']);
        if ($textContent !== null) {
            $lines = preg_split('/\r\n|\r|\n/', $textContent);
            foreach ($lines as $idx => $line) {
                if ($idx > 0) { $contentCell->addTextBreak(); }
                $contentCell->addText($line, ['name' => 'Arial', 'size' => 10]);
            }
        } elseif ($imagePath !== null && file_exists($imagePath)) {
            $contentCell->addImage($imagePath, [
                'width' => 500,
                'ratio' => true,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
        } else {
            $contentCell->addText('(Contenido no proporcionado)', ['name' => 'Arial', 'size' => 10]);
        }

        return $table;
    }

    /**
     * Mostrar vista del programa con anexos y poes.
     * Opcionalmente acepta ?company_id= para incluir envíos aprobados y registros de POE de la empresa.
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->query('company_id');
        $user = $request->user();
        $role = strtolower((string) optional($user)->rol);
        $isAdmin = in_array($role, ['admin', 'administrador', 'super-admin']);

        if ($companyId && ! $isAdmin) {
            $hasAccess = DB::table('company_user')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->exists();
            if (! $hasAccess) {
                abort(403, 'No tienes acceso a esta empresa');
            }
        }

        $program = Program::findOrFail($id);

        // Obtener anexos vinculados al programa, filtrando por configuracin de la empresa si aplica
        $annexIds = DB::table('program_annexes')->where('program_id', $program->id)->pluck('annex_id')->toArray();
        if ($companyId) {
            $assignedAnnexIds = DB::table('company_program_config')
                ->where('company_id', $companyId)
                ->where('program_id', $program->id)
                ->pluck('annex_id')
                ->toArray();

            if (count($assignedAnnexIds)) {
                $annexIds = array_values(array_intersect($annexIds, $assignedAnnexIds));
            } else {
                $annexIds = [];
            }
        }

        $annexCollection = count($annexIds)
            ? Annex::whereIn('id', $annexIds)->get()
            : collect();

        $annexes = $annexCollection->map(function ($a) use ($companyId, $program) {
            $files = [];
            $contentText = null;
            $tableData = null;
            $planillaData = null;
            $screenshotPath = null;
            $uploadedAt = null;
            $hasTextSubmission = false;
            $hasTableSubmission = false;
            $hasPlanillaSubmission = false;
            
            if ($companyId) {
                $subs = CompanyAnnexSubmission::where('company_id', $companyId)
                    ->where('program_id', $program->id)
                    ->where('annex_id', $a->id)
                    ->whereIn('status', ['Pendiente', 'Aprobado'])
                    ->get();

                foreach ($subs as $s) {
                    // Priorizar contenido de texto/tabla/planilla si existe en la submission
                    if (!empty($s->content_text)) {
                        // Si el anexo es tipo planilla, parsear JSON
                        if ($a->content_type === 'planilla') {
                            $hasPlanillaSubmission = true;
                            $planillaData = json_decode($s->content_text, true);
                            $screenshotPath = $s->screenshot_path;
                            $uploadedAt = $s->updated_at ? $s->updated_at->format('Y-m-d H:i') : ($s->created_at ? $s->created_at->format('Y-m-d H:i') : null);
                            continue;
                        }
                        // Si el anexo es tipo tabla, parsear JSON
                        if ($a->content_type === 'table') {
                            $hasTableSubmission = true;
                            $tableData = json_decode($s->content_text, true);
                            $uploadedAt = $s->updated_at ? $s->updated_at->format('Y-m-d H:i') : ($s->created_at ? $s->created_at->format('Y-m-d H:i') : null);
                            continue;
                        }
                        // Si no es tabla ni planilla, es texto plano
                        $hasTextSubmission = true;
                        $contentText = $s->content_text;
                        $uploadedAt = $s->updated_at ? $s->updated_at->format('Y-m-d H:i') : ($s->created_at ? $s->created_at->format('Y-m-d H:i') : null);
                        continue; // No procesar como archivo si es texto
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
                        // Establecer fecha más reciente de subida entre los archivos
                        $thisUploadedAt = $s->updated_at ? $s->updated_at->format('Y-m-d H:i') : ($s->created_at ? $s->created_at->format('Y-m-d H:i') : null);
                        if ($thisUploadedAt && (!$uploadedAt || $thisUploadedAt > $uploadedAt)) {
                            $uploadedAt = $thisUploadedAt;
                        }
                        $files[] = [
                            'id' => $s->id,
                            'name' => $s->file_name,
                            // Usar ruta interna /public-storage para evitar 403 con symlink en dev/Windows
                            'url' => url('public-storage/' . ltrim($s->file_path, '/')),
                            'mime' => $s->mime_type,
                            'uploaded_at' => $thisUploadedAt,
                        ];
                    }
                }
            }

            // Mapear tipo a la nomenclatura del frontend
            // El 'type' determina qué tipo de archivo aceptar
            // El 'content_type' determina si es imagen/archivo o texto
            // Por defecto, todos los anexos aceptan imágenes, a menos que sean de tipo texto
            if ($a->content_type === 'text' || $hasTextSubmission) {
                // Para anexos de texto, usar IMAGES como tipo por defecto (el frontend decidirá mostrar editor)
                $type = 'IMAGES';
            } else {
                // Para anexos de imagen/archivo, siempre usar IMAGES
                $type = 'IMAGES';
            }

            // content_type efectivo para el frontend: si hay submission de texto/tabla/planilla, marcar apropiadamente
            $effectiveContentType = $hasTextSubmission ? 'text' : ($hasTableSubmission ? 'table' : ($hasPlanillaSubmission ? 'planilla' : ($a->content_type ?? 'image')));

            return [
                'id' => $a->id,
                'name' => $a->nombre,
                'code' => $a->codigo_anexo,
                'type' => $type,
                'content_type' => $effectiveContentType, // Incluir content_type (image/text/table/planilla)
                'planilla_view' => $a->planilla_view,
                'content_text' => $contentText, // Incluir texto si existe
                'table_columns' => $a->table_columns, // Configuración de columnas para tabla
                'table_header_color' => $a->table_header_color, // Color de cabecera para tabla
                'table_data' => $tableData, // Datos de la tabla parseados
                'planilla_data' => $planillaData, // Datos de la planilla parseados
                'screenshot_path' => $screenshotPath, // Ruta del screenshot de la planilla
                'uploaded_at' => $uploadedAt,
                'files' => $files,
            ];
        })->values()->toArray();

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
                // Helper para construir URL pública de logos almacenados en storage/app/public
                $buildPublicUrl = function (?string $storagePath) {
                    if (!$storagePath) return null;
                    return url('public-storage/' . ltrim($storagePath, '/'));
                };
                
                // Buscar el PDF actual del programa para esta empresa
                $disk = Storage::disk('public');
                $baseRoot = 'company-documents/company_' . $company->id . '/program_' . $program->id;
                $pdfRel = $baseRoot . '/current.pdf';
                $hasPdf = $disk->exists($pdfRel);
                $pdfUrl = $hasPdf ? route('public.storage', ['path' => $pdfRel]) : null;
                $pdfMtime = null;
                $pdfUrlCacheBusted = null;
                if ($hasPdf) {
                    try {
                        $pdfAbs = $disk->path($pdfRel);
                        $pdfMtime = @filemtime($pdfAbs) ?: null;
                        if ($pdfMtime) {
                            $pdfUrlCacheBusted = $pdfUrl . '?v=' . $pdfMtime;
                        } else {
                            $pdfUrlCacheBusted = $pdfUrl;
                        }
                    } catch (\Throwable $e) {
                        $pdfUrlCacheBusted = $pdfUrl;
                    }
                }
                
                $companyPayload = [
                    'id' => $company->id,
                    'name' => $company->nombre,
                    'nit' => $company->nit_empresa,
                    'address' => $company->direccion,
                    'activities' => $company->actividades,
                    'representative' => $company->representante_legal,
                    'logo_left_url' => $buildPublicUrl($company->logo_izquierdo),
                    'logo_right_url' => $buildPublicUrl($company->logo_derecho),
                    'current_pdf_url' => $pdfUrl,
                    'current_pdf_mtime' => $pdfMtime,
                    'current_pdf_url_cache' => $pdfUrlCacheBusted,
                ];
            }
        }

        return Inertia::render('ProgramView', [
            'program' => $payload,
            'company' => $companyPayload,
        ]);
    }
}