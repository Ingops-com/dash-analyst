<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ProgramController extends Controller
{
    public function generatePdf(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string',
            'company_nit' => 'required|string',
            'company_address' => 'required|string',
            'company_activities' => 'required|string',
            'company_representative' => 'required|string',
            'program_name' => 'required|string',
            'anexos.*.archivo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
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

            $templatePath = storage_path('app/templates/Plantilla.docx');
            if (!file_exists($templatePath)) {
                throw new \Exception('El archivo de plantilla "Plantilla.docx" no se encontró en storage/app/templates/.');
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // Reemplazar variables de TEXTO
            $templateProcessor->setValue('Nombre de la empresa', $validated['company_name']);
            $templateProcessor->setValue('NIT', $validated['company_nit']);
            $templateProcessor->setValue('Dirección', $validated['company_address']);
            $templateProcessor->setValue('actividades de la empresa', $validated['company_activities']);
            $templateProcessor->setValue('Actividades de la empresa', $validated['company_activities']);
            $templateProcessor->setValue('Representante legal', $validated['company_representative']);
            $templateProcessor->setValue('Programa', $validated['program_name']);

            // Reemplazar placeholders de IMÁGENES
            $annexMapping = [
                'Certificado de Fumigación' => 'Anexo 1',
                'Factura de Insumos' => 'Anexo 2',
                'Registro Fotográfico' => 'Anexo 3',
                'Checklist Interno' => 'Anexo 4',
                'Memorando Aprobación' => 'Anexo 5',
            ];

            if ($request->has('anexos')) {
                foreach ($request->all()['anexos'] as $index => $anexoData) {
                    if ($request->hasFile("anexos.{$index}.archivo")) {
                        $file = $request->file("anexos.{$index}.archivo");
                        $title = $anexoData['titulo'];

                        if (isset($annexMapping[$title]) && str_starts_with($file->getMimeType(), 'image/')) {
                            $newImageName = uniqid('anexo_', true) . '.' . $file->getClientOriginalExtension();
                            $newImagePath = $tempDir . '/' . $newImageName;
                            $file->move($tempDir, $newImageName);
                            $tempImagePaths[] = $newImagePath;

                            $templateProcessor->setImageValue($annexMapping[$title], [
                                'path' => $newImagePath,
                                'width' => 500,
                                'ratio' => true
                            ]);
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
}