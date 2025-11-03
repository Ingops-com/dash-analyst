<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompanyAnnexSubmission;
use Illuminate\Support\Facades\Storage;

echo "=== Diagnóstico de Archivo Subido ===\n\n";

$submission = CompanyAnnexSubmission::where('company_id', 1)
    ->where('program_id', 1)
    ->latest()
    ->first();

if (!$submission) {
    echo "❌ No se encontró ningún archivo en la BD\n";
    exit;
}

echo "📄 Información del archivo en BD:\n";
echo "   ID: {$submission->id}\n";
echo "   Nombre: {$submission->file_name}\n";
echo "   Ruta en BD: {$submission->file_path}\n";
echo "   Tipo: {$submission->mime_type}\n";
echo "   Tamaño: " . number_format($submission->file_size / 1024, 2) . " KB\n";
echo "   Status: {$submission->status}\n";
echo "   Creado: {$submission->created_at}\n\n";

echo "🔍 Verificación de rutas:\n";

// Ruta 1: Como está en la BD
$path1 = storage_path('app/' . $submission->file_path);
echo "   1. storage_path('app/' . file_path):\n";
echo "      {$path1}\n";
echo "      Existe: " . (file_exists($path1) ? "✅ SÍ" : "❌ NO") . "\n\n";

// Ruta 2: Usando Storage::path()
try {
    $path2 = Storage::path($submission->file_path);
    echo "   2. Storage::path(file_path):\n";
    echo "      {$path2}\n";
    echo "      Existe: " . (file_exists($path2) ? "✅ SÍ" : "❌ NO") . "\n\n";
} catch (\Exception $e) {
    echo "   2. Storage::path() falló: {$e->getMessage()}\n\n";
}

// Ruta 3: Usando Storage::exists()
$exists = Storage::exists($submission->file_path);
echo "   3. Storage::exists(file_path): " . ($exists ? "✅ SÍ" : "❌ NO") . "\n\n";

// Listar todos los archivos en el directorio de anexos
echo "📁 Archivos en storage/app/public/anexos/:\n";
$basePath = storage_path('app/public/anexos');
if (is_dir($basePath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            echo "   - " . $file->getPathname() . " (" . number_format($file->getSize() / 1024, 2) . " KB)\n";
        }
    }
} else {
    echo "   ❌ El directorio no existe\n";
}

echo "\n💡 URL pública esperada:\n";
echo "   " . Storage::url($submission->file_path) . "\n";
