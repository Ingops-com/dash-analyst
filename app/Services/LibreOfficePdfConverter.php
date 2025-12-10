<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LibreOfficePdfConverter
{
    /**
     * Convierte un archivo DOCX a PDF usando LibreOffice
     * 
     * @param string $docxPath Ruta completa al archivo DOCX
     * @param string $outputDir Directorio donde se guardará el PDF (opcional)
     * @return string|false Ruta al PDF generado o false en caso de error
     */
    public static function convertToPdf(string $docxPath, string $outputDir = null): string|false
    {
        // Validar que el archivo existe
        if (!file_exists($docxPath)) {
            Log::error('LibreOffice: Archivo DOCX no encontrado', ['path' => $docxPath]);
            return false;
        }

        // Directorio de salida (por defecto, mismo directorio del DOCX)
        if ($outputDir === null) {
            $outputDir = dirname($docxPath);
        }

        // Crear directorio de salida si no existe
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Detectar ruta de LibreOffice según el sistema operativo
        $libreOfficePath = self::detectLibreOfficePath();
        
        if ($libreOfficePath === false) {
            Log::error('LibreOffice no encontrado en el sistema');
            return false;
        }

        Log::info('LibreOffice detectado', ['path' => $libreOfficePath]);
        
        // Usar Symfony Process (método simplificado que funciona)
        try {
            $filename = basename($docxPath);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $expectedPdfPath = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';
            
            // Comando simple como en PdfConversionController
            $command = [
                $libreOfficePath,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDir,
                $docxPath
            ];
            
            Log::info('Ejecutando conversión LibreOffice', [
                'command' => implode(' ', $command),
                'expected_pdf' => $expectedPdfPath
            ]);
            
            // NO cambiar el directorio de trabajo - pasar null como segundo parámetro
            $process = new \Symfony\Component\Process\Process($command);
            $process->setTimeout(120);
            $process->run();
            
            // Log de salida para debugging
            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            if (!empty($stdout) || !empty($stderr)) {
                Log::info('LibreOffice output', [
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'exit_code' => $process->getExitCode()
                ]);
            }
            
            if (!$process->isSuccessful()) {
                Log::error('LibreOffice falló', [
                    'exit_code' => $process->getExitCode(),
                    'output' => $stdout,
                    'error' => $stderr
                ]);
                return false;
            }
            
            if (file_exists($expectedPdfPath) && filesize($expectedPdfPath) > 1024) {
                Log::info('PDF generado exitosamente con LibreOffice', [
                    'path' => $expectedPdfPath,
                    'size_kb' => round(filesize($expectedPdfPath) / 1024, 2)
                ]);
                return $expectedPdfPath;
            } else {
                Log::error('LibreOffice no generó el PDF esperado', [
                    'expected' => $expectedPdfPath,
                    'exists' => file_exists($expectedPdfPath)
                ]);
                return false;
            }
            
        } catch (\Throwable $e) {
            Log::error('Excepción en conversión LibreOffice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * MÉTODO LEGACY - Mantener por compatibilidad pero no usar
     */
    private static function convertToPdfLegacy(string $docxPath, string $outputDir = null): string|false
    {
        // Validar que el archivo existe
        if (!file_exists($docxPath)) {
            Log::error('LibreOffice: Archivo DOCX no encontrado', ['path' => $docxPath]);
            return false;
        }

        // Directorio de salida (por defecto, mismo directorio del DOCX)
        if ($outputDir === null) {
            $outputDir = dirname($docxPath);
        }

        // Crear directorio de salida si no existe
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Detectar ruta de LibreOffice según el sistema operativo
        $libreOfficePath = self::detectLibreOfficePath();
        
        if ($libreOfficePath === false) {
            Log::error('LibreOffice no encontrado en el sistema');
            return false;
        }

        Log::info('LibreOffice detectado (legacy)', ['path' => $libreOfficePath]);

        // TODO: Legacy code removed - now using simplified Symfony Process method above
    }

    /**
     * Detecta la ruta de LibreOffice en el sistema
     * 
     * @return string|false Ruta al ejecutable de LibreOffice o false si no se encuentra
     */
    private static function detectLibreOfficePath(): string|false
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Rutas comunes en Windows
            $possiblePaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 7\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 6\\program\\soffice.exe',
                'C:\\Program Files\\LibreOffice 5\\program\\soffice.exe',
                getenv('LIBREOFFICE_PATH'), // Variable de entorno personalizada
            ];
        } else {
            // Rutas comunes en Linux/Mac
            $possiblePaths = [
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/local/bin/libreoffice',
                '/usr/local/bin/soffice',
                '/Applications/LibreOffice.app/Contents/MacOS/soffice',
                getenv('LIBREOFFICE_PATH'), // Variable de entorno personalizada
            ];
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // Intentar encontrar usando 'where' (Windows) o 'which' (Linux/Mac)
        if (PHP_OS_FAMILY === 'Windows') {
            $result = shell_exec('where soffice.exe 2>nul');
        } else {
            $result = shell_exec('which libreoffice 2>/dev/null || which soffice 2>/dev/null');
        }

        if ($result) {
            $path = trim(explode("\n", $result)[0]);
            if (file_exists($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Verifica si LibreOffice está disponible en el sistema
     * 
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return self::detectLibreOfficePath() !== false;
    }

    /**
     * Obtiene la versión de LibreOffice instalada
     * 
     * @return string|null
     */
    public static function getVersion(): ?string
    {
        $libreOfficePath = self::detectLibreOfficePath();
        
        if ($libreOfficePath === false) {
            return null;
        }

        $command = sprintf('"%s" --version 2>&1', $libreOfficePath);
        $output = shell_exec($command);
        
        if ($output && preg_match('/LibreOffice\s+([\d.]+)/i', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
