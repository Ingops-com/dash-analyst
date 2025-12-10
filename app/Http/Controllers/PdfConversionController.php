<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class PdfConversionController extends Controller
{
    /**
     * Extensiones permitidas para conversión
     */
    private const ALLOWED_EXTENSIONS = [
        'xls', 'xlsx', 'xlsm', 'xlsb', 'ods',  // Excel
        'doc', 'docx', 'odt'                     // Word
    ];

    /**
     * Timeout para conversión en segundos
     */
    private const CONVERSION_TIMEOUT = 120;

    /**
     * Tamaño máximo de archivo (20 MB)
     */
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    /**
     * Health check endpoint
     */
    public function health()
    {
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Convierte un archivo Office a PDF usando LibreOffice
     */
    public function convert(Request $request)
    {
        // Validar que venga el archivo
        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => 'No se proporcionó ningún archivo'
            ], 400);
        }

        $file = $request->file('file');

        // Validar que se seleccionó un archivo
        if (!$file->isValid()) {
            return response()->json([
                'error' => 'El archivo no es válido'
            ], 400);
        }

        // Validar extensión
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return response()->json([
                'error' => 'Extensión de archivo no permitida',
                'allowed_extensions' => self::ALLOWED_EXTENSIONS
            ], 400);
        }

        // Validar tamaño
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'error' => 'El archivo es demasiado grande',
                'details' => 'Supera el tamaño máximo permitido (20 MB)'
            ], 413);
        }

        // Crear directorio temporal único
        $tempDir = storage_path('app/temp/office2pdf_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        $filename = $file->getClientOriginalName();
        $inputPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        try {
            // Guardar archivo de entrada
            $file->move($tempDir, $filename);
            Log::info("Archivo recibido y guardado", ['path' => $inputPath]);

            // Nombre esperado para el PDF
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $pdfName = $baseName . '.pdf';
            $outputPath = $tempDir . DIRECTORY_SEPARATOR . $pdfName;

            // Detectar LibreOffice
            $libreOfficePath = $this->detectLibreOfficePath();
            if (!$libreOfficePath) {
                return response()->json([
                    'error' => 'LibreOffice no está instalado o no se encuentra en el sistema'
                ], 500);
            }

            // Construir comando simple como en Flask API
            // Using just the filename since working dir will be set to tempDir
            $command = [
                $libreOfficePath,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                '.',  // Output to current directory (temp dir)
                $filename  // Just the filename, not full path
            ];

            Log::info("Iniciando conversión con LibreOffice", ['command' => implode(' ', $command)]);

            // Crear proceso con directorio de trabajo establecido
            $process = new Process($command, $tempDir); // Set working directory to temp dir
            $process->setTimeout(self::CONVERSION_TIMEOUT);

            try {
                $process->run();

                // Log output regardless of success
                $stderr = $process->getErrorOutput();
                $stdout = $process->getOutput();
                $exitCode = $process->getExitCode();
                
                Log::info("LibreOffice execution completed", [
                    'exit_code' => $exitCode,
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'successful' => $process->isSuccessful()
                ]);

                if (!$process->isSuccessful()) {
                    Log::error("Error en LibreOffice", [
                        'returncode' => $exitCode,
                        'stdout' => $stdout,
                        'stderr' => $stderr
                    ]);

                    return response()->json([
                        'error' => 'Error al convertir el archivo',
                        'details' => trim($stderr ?: $stdout)
                    ], 500);
                }

            } catch (ProcessTimedOutException $e) {
                Log::error("Timeout al convertir archivo", ['filename' => $filename]);
                
                return response()->json([
                    'error' => 'Timeout al convertir el archivo',
                    'details' => "La conversión excedió los " . self::CONVERSION_TIMEOUT . " segundos permitidos."
                ], 500);
            }

            // Verificar que el PDF fue generado
            // List all files in temp dir for debugging
            $filesInTempDir = scandir($tempDir);
            Log::info("Files in temp directory after conversion", [
                'temp_dir' => $tempDir,
                'files' => $filesInTempDir
            ]);
            
            if (!file_exists($outputPath)) {
                Log::error("PDF no fue generado", [
                    'expected_path' => $outputPath,
                    'files_in_dir' => $filesInTempDir
                ]);
                
                return response()->json([
                    'error' => 'El archivo PDF no fue generado'
                ], 500);
            }

            Log::info("Conversión completada correctamente", ['filename' => $filename]);

            // Retornar el archivo PDF
            return response()->download($outputPath, $pdfName, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(false); // No eliminar aún, lo haremos en finally

        } catch (\Exception $e) {
            Log::error("Error inesperado durante la conversión", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'details' => $e->getMessage()
            ], 500);

        } finally {
            // Eliminar directorio temporal completo
            try {
                if (File::isDirectory($tempDir)) {
                    // Esperar un momento para que el archivo termine de enviarse
                    sleep(1);
                    File::deleteDirectory($tempDir);
                    Log::info("Directorio temporal eliminado", ['path' => $tempDir]);
                }
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar el directorio temporal", [
                    'path' => $tempDir,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Detecta la ruta de LibreOffice en el sistema
     */
    private function detectLibreOfficePath(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $possiblePaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Intentar con 'where'
            $result = shell_exec('where soffice.exe 2>nul');
            if ($result) {
                $path = trim(explode("\n", $result)[0]);
                if (file_exists($path)) {
                    return $path;
                }
            }
        } else {
            // Linux/Mac
            $result = shell_exec('which libreoffice 2>/dev/null || which soffice 2>/dev/null');
            if ($result) {
                $path = trim($result);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }
}
