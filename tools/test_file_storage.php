<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "=== Test de Subida de Archivo ===\n\n";

// Crear un archivo de prueba
$testContent = "Este es un archivo de prueba";
$tempFile = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($tempFile, $testContent);

echo "1. Archivo temporal creado: {$tempFile}\n";
echo "   Existe: " . (file_exists($tempFile) ? "✅" : "❌") . "\n\n";

// Simular UploadedFile
$uploadedFile = new UploadedFile(
    $tempFile,
    'test-upload.txt',
    'text/plain',
    null,
    true
);

echo "2. UploadedFile creado\n";
echo "   Nombre: {$uploadedFile->getClientOriginalName()}\n";
echo "   Tipo: {$uploadedFile->getMimeType()}\n\n";

// Intentar guardar usando storeAs
$storagePath = "anexos/company_test/program_test";
$uniqueName = uniqid() . '_test-upload.txt';

echo "3. Intentando guardar con storeAs...\n";
echo "   Ruta: public/{$storagePath}\n";
echo "   Nombre: {$uniqueName}\n\n";

try {
    $filePath = $uploadedFile->storeAs("public/{$storagePath}", $uniqueName);
    echo "   ✅ storeAs retornó: {$filePath}\n\n";
    
    // Verificar si existe
    echo "4. Verificando existencia:\n";
    echo "   Storage::exists('{$filePath}'): " . (Storage::exists($filePath) ? "✅" : "❌") . "\n";
    
    $fullPath = storage_path('app/' . $filePath);
    echo "   Ruta completa: {$fullPath}\n";
    echo "   file_exists(): " . (file_exists($fullPath) ? "✅" : "❌") . "\n\n";
    
    if (file_exists($fullPath)) {
        echo "   📄 Contenido: " . file_get_contents($fullPath) . "\n\n";
        
        // Limpiar
        Storage::delete($filePath);
        echo "   🗑️ Archivo de prueba eliminado\n";
    } else {
        echo "   ❌ El archivo no se guardó donde se esperaba\n";
        echo "   📁 Listando archivos en storage/app/public/anexos/:\n";
        
        $dir = storage_path('app/public/anexos');
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                echo "      - {$file->getPathname()}\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
}

// Limpiar archivo temporal
@unlink($tempFile);
