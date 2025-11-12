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

        // Fallback: si llega content_text (JSON o form-data) y no viene archivo, tratar como texto
        $treatAsText = ($annex->content_type === 'text') || ($request->has('content_text') && !$request->hasFile('file'));

        Log::info('uploadAnnex called', [
            'program_id' => $programId,
            'annex_id' => $annexId,
            'annex_content_type' => $annex->content_type,
            'has_file' => $request->hasFile('file'),
            'has_content_text' => $request->has('content_text'),
            'treat_as_text' => $treatAsText,
        ]);

        if ($treatAsText) {
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
                    // Para anexos de texto: generar imagen con metadata + contenido de texto
                    try {
                        // Obtener el contenido de texto de la primera submission
                        $textSubmission = $submissions->first();
                        $htmlContent = $textSubmission?->content_text ?? '';
                        
                        if (!empty($htmlContent)) {
                            // Convertir HTML a texto plano
                            $textContent = $this->convertHtmlToText($htmlContent);
                            
                            // Generar imagen del texto
                            $textImagePath = $this->renderTextAsImage($textContent);
                            $tempImagePaths[] = $textImagePath;
                            
                            // Combinar metadata + texto en una sola imagen
                            $combinedImagePath = $this->combineImagesVertically($metaImagePath, [$textImagePath]);
                            $tempImagePaths[] = $combinedImagePath;
                            
                            // Crear copia única para evitar conflictos con imágenes de plantilla
                            $uniquePath = $this->createUniqueImageCopy($combinedImagePath);
                            $tempImagePaths[] = $uniquePath;
                            
                            $templateProcessor->setImageValue($placeholder, [
                                'path' => $uniquePath,
                                'width' => 500,
                                'ratio' => true
                            ]);
                            $placeholdersWithImage[$placeholder] = true;
                            Log::info("Anexo de texto con metadata + contenido insertado: {$annexInfo->nombre}");
                        } else {
                            // Sin contenido de texto, solo insertar metadata
                            $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                            $tempImagePaths[] = $uniquePath;
                            
                            $templateProcessor->setImageValue($placeholder, [
                                'path' => $uniquePath,
                                'width' => 500,
                                'ratio' => true
                            ]);
                            $placeholdersWithImage[$placeholder] = true;
                            Log::info("Anexo de texto sin contenido, solo metadata insertada: {$annexInfo->nombre}");
                        }
                    } catch (\Throwable $e) {
                        $templateProcessor->setValue($placeholder, '(Anexo de texto)');
                        $placeholdersWithImage[$placeholder] = true;
                        Log::warning("Fallback metadata texto en setValue: {$annexInfo->nombre} -> {$e->getMessage()}");
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

                if (!count($annexImages)) {
                    // Sin imágenes del anexo: insertar solo metadata
                    try {
                        // Crear copia única para evitar conflictos con imágenes de plantilla
                        $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                        $tempImagePaths[] = $uniquePath;
                        
                        $templateProcessor->setImageValue($placeholder, [
                            'path' => $uniquePath,
                            'width' => 500,
                            'ratio' => true
                        ]);
                        $placeholdersWithImage[$placeholder] = true;
                        Log::info("Anexo sin imágenes: solo metadata insertada para {$annexInfo?->nombre}");
                    } catch (\Throwable $e) {
                        $templateProcessor->setValue($placeholder, '(Anexo no proporcionado)');
                        Log::warning("Error insertando metadata sola: {$e->getMessage()}");
                    }
                    continue;
                }

                // Combinar metadata + imágenes del anexo en un solo PNG vertical
                try {
                    $combinedImagePath = $this->combineImagesVertically($metaImagePath, $annexImages);
                    $tempImagePaths[] = $combinedImagePath;
                    
                    // Crear copia única para evitar conflictos con imágenes de plantilla
                    $uniquePath = $this->createUniqueImageCopy($combinedImagePath);
                    $tempImagePaths[] = $uniquePath;
                    
                    $templateProcessor->setImageValue($placeholder, [
                        'path' => $uniquePath,
                        'width' => 500,
                        'ratio' => true
                    ]);
                    $placeholdersWithImage[$placeholder] = true;
                    Log::info("Anexo combinado (metadata + " . count($annexImages) . " imágenes) insertado: {$annexInfo?->nombre}");
                } catch (\Throwable $e) {
                    // Fallback: intentar insertar solo metadata
                    try {
                        // Crear copia única para evitar conflictos con imágenes de plantilla
                        $uniquePath = $this->createUniqueImageCopy($metaImagePath);
                        $tempImagePaths[] = $uniquePath;
                        
                        $templateProcessor->setImageValue($placeholder, [
                            'path' => $uniquePath,
                            'width' => 500,
                            'ratio' => true
                        ]);
                        $placeholdersWithImage[$placeholder] = true;
                        Log::warning("Fallback a solo metadata para anexo {$annexInfo?->nombre}: {$e->getMessage()}");
                    } catch (\Throwable $inner) {
                        $templateProcessor->setValue($placeholder, '(Error inserción anexo)');
                        Log::error("Error total insertando anexo {$annexInfo?->nombre}: {$inner->getMessage()}");
                    }
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

            // ===== PRE-PROCESAR DOCX PARA ELIMINAR IMÁGENES WMF/EMF QUE ROMPEN PhpWord =====
            $finalDocxPath = $this->sanitizeDocxImages($finalDocxPath);

            // ===== AGREGAR HEADER PERSONALIZADO =====
            // En algunos casos las plantillas incluyen imágenes WMF/EMF que el lector de PhpWord no soporta.
            // Si ocurre, hacemos un fallback: entregamos el documento sin la inyección de header
            // y avisamos en el log para que se reemplacen los WMF por PNG/JPG en la plantilla.
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($finalDocxPath);
                $headerService = new DocumentHeaderService();

                // Detectar anexo más recientemente actualizado para mostrarlo en header (si existe)
                $recentSubmission = null;
                if (isset($existingSubmissions) && $existingSubmissions->count()) {
                    $recentSubmission = $existingSubmissions->sortByDesc('updated_at')->first();
                }
                $annexHeaderData = null;
                if ($recentSubmission) {
                    $recentAnnex = Annex::find($recentSubmission->annex_id);
                    if ($recentAnnex) {
                        $annexHeaderData = [
                            'name' => $recentAnnex->nombre,
                            'code' => $recentAnnex->codigo_anexo,
                            'uploaded_at' => $recentSubmission->updated_at ? $recentSubmission->updated_at->format('Y-m-d H:i') : null,
                        ];
                    }
                }

                foreach ($phpWord->getSections() as $section) {
                    $headerService->createCustomHeader($section, $companyData, $programData);
                    $headerService->addFooterLogo($section, $companyData['logo_pie_de_pagina']);
                }

                // Agregar tabla de reemplazo de anexo en el cuerpo si hubo un anexo reciente
                if ($annexHeaderData) {
                    $this->addAnnexReplacementTable($phpWord, $companyData, $annexHeaderData);
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
     * Eliminar imágenes WMF/EMF del DOCX para evitar errores "Invalid image" al cargar con PhpWord.
     * Crea una copia limpia del archivo si se modificó algo.
     *
     * @param string $docxPath
     * @return string Ruta (posiblemente nueva) del DOCX saneado.
     */
    private function sanitizeDocxImages(string $docxPath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return $docxPath; // Sin soporte Zip, no podemos sanear
        }
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return $docxPath; // No se pudo abrir
        }
        $filesToRemove = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) continue;
            $name = $stat['name'];
            // Remover imágenes WMF/EMF
            if (preg_match('/word\/media\/image\\d+\.(wmf|emf)$/i', $name)) {
                $filesToRemove[] = $name;
            }
        }

        if (!count($filesToRemove)) {
            $zip->close();
            return $docxPath; // Nada que eliminar
        }

        // Crear copia temporal para modificar (evitar corrupción si falla)
        $sanitizedPath = preg_replace('/\.docx$/', '_sanitized.docx', $docxPath);
        copy($docxPath, $sanitizedPath);
        $zip->close();

        // Reabrir copia y eliminar
        if ($zip->open($sanitizedPath) === true) {
            foreach ($filesToRemove as $f) {
                $zip->deleteName($f);
            }
            $zip->close();
            \Illuminate\Support\Facades\Log::warning('Sanitized DOCX removed unsupported images', ['removed' => $filesToRemove]);
            return $sanitizedPath;
        } else {
            return $docxPath; // Fallback
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

        // Dimensiones base (estructura de 3 columnas) - AUMENTADAS para mejor legibilidad
        $width = 2400; 
        $height = 450;
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
            $drawScaled($im, $leftLogo, $col1X + 10, 10, $col1W - 20, $height - 20);
            imagedestroy($leftLogo);
        }

        // === COLUMNA 2: TÍTULO CON FONDO DORADO ===
        imagefilledrectangle($im, $col2X + 1, 1, $col3X - 1, $height - 2, $goldBg);
        
        $title = strtoupper($annexHeaderData['name'] ?? 'ANEXO');
        if ($useTTF) {
            $titleFontSize = 36;
            $titleY = (int)($height / 2 + 12); // Ajuste vertical para baseline TTF
            $drawTextCenteredTTF($im, $title, $col2X, $titleY, $col2W, $titleFontSize, $white, $fontPath);
        } else {
            $fontTitle = 5;
            $titleY = (int)($height / 2 - imagefontheight($fontTitle) / 2);
            $drawTextCentered($im, $title, $col2X, $titleY, $col2W, $fontTitle, $white);
        }

        // === COLUMNA 3: PANEL DERECHO (4 FILAS) ===
        // Dividir en 4 filas iguales para: Versión | Logo pequeño | Fecha | Código
        $rowHeight = (int)($height / 4);
        
        // Dibujar líneas horizontales para separar filas en col3
        for ($i = 1; $i < 4; $i++) {
            $lineY = $i * $rowHeight;
            imageline($im, $col3X, $lineY, $width - 1, $lineY, $black);
        }
        
        // FILA 1: "Versión" + número
        $row1Y = 15;
        $versionLabel = "Version";
        $versionValue = $companyData['version'] ?? '1';
        if ($useTTF) {
            $drawTextCenteredTTF($im, $versionLabel, $col3X, $row1Y + 28, $col3W, 20, $black, $fontPath);
            $drawTextCenteredTTF($im, $versionValue, $col3X, $row1Y + 75, $col3W, 26, $black, $fontPath);
        } else {
            $drawTextCentered($im, $versionLabel, $col3X, $row1Y, $col3W, 4, $black);
            $drawTextCentered($im, $versionValue, $col3X, $row1Y + 30, $col3W, 5, $black);
        }

        // FILA 2: Logo pequeño (derecho)
        $row2Y = $rowHeight;
        $rightLogo = $loadImage($companyData['logo_derecho'] ?? null);
        if ($rightLogo) {
            $drawScaled($im, $rightLogo, $col3X + 15, $row2Y + 8, $col3W - 30, $rowHeight - 16);
            imagedestroy($rightLogo);
        }

        // FILA 3: "Fecha" + fecha formateada
        $row3Y = $rowHeight * 2;
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
            $drawTextCenteredTTF($im, $fechaLabel, $col3X, $row3Y + 28, $col3W, 20, $black, $fontPath);
            $drawTextCenteredTTF($im, $fechaFmt, $col3X, $row3Y + 75, $col3W, 22, $black, $fontPath);
        } else {
            $drawTextCentered($im, $fechaLabel, $col3X, $row3Y + 5, $col3W, 4, $black);
            $drawTextCentered($im, $fechaFmt, $col3X, $row3Y + 35, $col3W, 3, $black);
        }

        // FILA 4: "Código" (fondo azul marino) + código del anexo
        $row4Y = $rowHeight * 3;
        imagefilledrectangle($im, $col3X + 1, $row4Y + 1, $width - 2, $height - 2, $navyBlue);
        $codigoLabel = "Codigo";
        $codigoValue = $annexHeaderData['code'] ?? '';
        if ($useTTF) {
            $drawTextCenteredTTF($im, $codigoLabel, $col3X, $row4Y + 28, $col3W, 20, $white, $fontPath);
            $drawTextCenteredTTF($im, $codigoValue, $col3X, $row4Y + 75, $col3W, 22, $white, $fontPath);
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

        // Cargar imagen de metadata
        $metaImg = @imagecreatefrompng($metadataImagePath);
        if (!$metaImg) {
            throw new \Exception("No se pudo cargar imagen de metadata: {$metadataImagePath}");
        }

        $metaWidth = imagesx($metaImg);
        $metaHeight = imagesy($metaImg);

        // Cargar todas las imágenes del anexo y calcular dimensiones
        $images = [];
        $totalHeight = $metaHeight;
        $maxWidth = $metaWidth;
        $spacing = 20; // Espacio entre imágenes

        foreach ($annexImages as $path) {
            $img = @imagecreatefromstring(file_get_contents($path));
            if (!$img) {
                Log::warning("No se pudo cargar imagen del anexo: {$path}");
                continue;
            }
            $images[] = [
                'resource' => $img,
                'width' => imagesx($img),
                'height' => imagesy($img),
            ];
            $totalHeight += imagesy($img) + $spacing;
            $maxWidth = max($maxWidth, imagesx($img));
        }

        if (empty($images)) {
            // Si no hay imágenes del anexo, retornar solo la metadata
            imagedestroy($metaImg);
            return $metadataImagePath;
        }

        // Crear imagen combinada
        $combined = imagecreatetruecolor($maxWidth, $totalHeight);
        $white = imagecolorallocate($combined, 255, 255, 255);
        imagefilledrectangle($combined, 0, 0, $maxWidth - 1, $totalHeight - 1, $white);

        // Copiar metadata al inicio
        $currentY = 0;
        $metaX = (int)(($maxWidth - $metaWidth) / 2);
        imagecopy($combined, $metaImg, $metaX, $currentY, 0, 0, $metaWidth, $metaHeight);
        $currentY += $metaHeight + $spacing;
        imagedestroy($metaImg);

        // Copiar cada imagen del anexo
        foreach ($images as $imgData) {
            $imgX = (int)(($maxWidth - $imgData['width']) / 2);
            imagecopy($combined, $imgData['resource'], $imgX, $currentY, 0, 0, $imgData['width'], $imgData['height']);
            $currentY += $imgData['height'] + $spacing;
            imagedestroy($imgData['resource']);
        }

        // Guardar imagen combinada
        $outPath = $tempDir . '/' . uniqid('combined_annex_', true) . '.png';
        imagepng($combined, $outPath);
        imagedestroy($combined);

        Log::info('Imagen combinada verticalmente (metadata + anexos)', [
            'path' => $outPath,
            'images_count' => count($images),
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

        // Dimensiones iniciales
        $width = 2400; // Mismo ancho que la metadata para coherencia
        $lineHeight = 35;
        $padding = 40;
        $fontSize = 4; // Fuente GD (o intentar TTF si está disponible)
        
        // Intentar cargar fuente TrueType
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        $useTTF = file_exists($fontPath);
        $ttfSize = 18;

        // Dividir texto en líneas (respetando saltos de línea y wrapping)
        $maxCharsPerLine = $useTTF ? 120 : 150;
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

        // Calcular altura necesaria
        $height = $padding * 2 + (count($lines) * $lineHeight);
        
        // Crear imagen
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

        Log::info('Texto renderizado como imagen', ['path' => $outPath, 'lines' => count($lines)]);
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

        $program = Program::findOrFail($id);

        // Obtener anexos vinculados al programa
        $annexIds = DB::table('program_annexes')->where('program_id', $program->id)->pluck('annex_id')->toArray();
        $annexes = Annex::whereIn('id', $annexIds)->get()->map(function ($a) use ($companyId, $program) {
            $files = [];
            $contentText = null;
            $uploadedAt = null;
            $hasTextSubmission = false;
            
            if ($companyId) {
                $subs = CompanyAnnexSubmission::where('company_id', $companyId)
                    ->where('program_id', $program->id)
                    ->where('annex_id', $a->id)
                    ->whereIn('status', ['Pendiente', 'Aprobado'])
                    ->get();

                foreach ($subs as $s) {
                    // Priorizar contenido de texto si existe en la submission, independientemente del content_type configurado
                    if (!empty($s->content_text)) {
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

            // content_type efectivo para el frontend: si hay submission de texto, marcar como 'text' para mostrar editor
            $effectiveContentType = $hasTextSubmission ? 'text' : ($a->content_type ?? 'image');

            return [
                'id' => $a->id,
                'name' => $a->nombre,
                'code' => $a->codigo_anexo,
                'type' => $type,
                'content_type' => $effectiveContentType, // Incluir content_type (image/text)
                'content_text' => $contentText, // Incluir texto si existe
                'uploaded_at' => $uploadedAt,
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
                // Helper para construir URL pública de logos almacenados en storage/app/public
                $buildPublicUrl = function (?string $storagePath) {
                    if (!$storagePath) return null;
                    return url('public-storage/' . ltrim($storagePath, '/'));
                };
                $companyPayload = [
                    'id' => $company->id,
                    'name' => $company->nombre,
                    'nit' => $company->nit_empresa,
                    'address' => $company->direccion,
                    'activities' => $company->actividades,
                    'representative' => $company->representante_legal,
                    'logo_left_url' => $buildPublicUrl($company->logo_izquierdo),
                    'logo_right_url' => $buildPublicUrl($company->logo_derecho),
                ];
            }
        }

        return Inertia::render('ProgramView', [
            'program' => $payload,
            'company' => $companyPayload,
        ]);
    }
}