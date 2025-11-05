<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "=== Verificador de Plantillas ===\n\n";

// 1. Listar archivos .docx en storage/plantillas/
$plantillasPath = storage_path('plantillas');

echo "📁 Plantillas disponibles en: {$plantillasPath}\n\n";

if (!File::exists($plantillasPath)) {
    echo "⚠ El directorio storage/plantillas/ no existe\n";
    echo "   Creando directorio...\n";
    File::makeDirectory($plantillasPath, 0755, true);
}

$files = File::allFiles($plantillasPath);
$docxFiles = array_filter($files, function($file) {
    return $file->getExtension() === 'docx';
});

if (empty($docxFiles)) {
    echo "⚠ No se encontraron plantillas .docx\n\n";
} else {
    echo "Plantillas encontradas:\n";
    foreach ($docxFiles as $file) {
        $relativePath = str_replace($plantillasPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relativePath = str_replace('\\', '/', $relativePath);
        echo "  ✓ {$relativePath}\n";
    }
    echo "\n";
}

// 2. Listar programas y sus plantillas configuradas
echo "=== Programas y sus plantillas ===\n\n";

$programs = DB::table('programs')->select('id', 'nombre', 'codigo', 'template_path')->get();

foreach ($programs as $program) {
    echo "ID {$program->id}: {$program->nombre} ({$program->codigo})\n";
    
    if ($program->template_path) {
        $fullPath = storage_path('plantillas/' . $program->template_path);
        $exists = file_exists($fullPath);
        
        if ($exists) {
            echo "  ✓ Plantilla: {$program->template_path} (existe)\n";
        } else {
            echo "  ✗ Plantilla: {$program->template_path} (NO EXISTE)\n";
            echo "    Esperada en: {$fullPath}\n";
        }
    } else {
        echo "  ⚠ Sin plantilla configurada\n";
    }
    echo "\n";
}

// 3. Sugerencias
echo "=== Sugerencias ===\n\n";

echo "Para configurar una plantilla:\n";
echo "1. Coloca el archivo .docx en storage/plantillas/carpeta/archivo.docx\n";
echo "2. Actualiza el programa:\n";
echo "   UPDATE programs SET template_path = 'carpeta/archivo.docx' WHERE id = X;\n\n";

echo "Para crear un nuevo programa con plantilla:\n";
echo "1. Crea el programa desde la interfaz\n";
echo "2. Llena el campo 'Ruta Plantilla' con: carpeta/archivo.docx\n";
