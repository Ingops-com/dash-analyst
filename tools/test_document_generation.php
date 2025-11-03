<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompanyAnnexSubmission;
use App\Models\Annex;

echo "=== Verificación de Archivos para Generación de Documento ===\n";
echo "Company ID: 1, Program ID: 1\n\n";

$submissions = CompanyAnnexSubmission::where('company_id', 1)
    ->where('program_id', 1)
    ->whereIn('status', ['Pendiente', 'Aprobado'])
    ->with('annex')
    ->get();

echo "Total de archivos encontrados: " . $submissions->count() . "\n\n";

// Agrupar por anexo
$grouped = $submissions->groupBy('annex_id');

foreach ($grouped as $annexId => $files) {
    $annex = $files->first()->annex;
    $annexName = $annex ? $annex->nombre : "Annex ID $annexId";
    
    echo "📎 {$annexName} (ID: {$annexId})\n";
    echo "   Total de archivos: " . $files->count() . "\n";
    echo "   Archivo que se usará en el documento: " . $files->first()->file_name . "\n";
    
    // Verificar que el archivo existe físicamente
    $filePath = storage_path('app/' . $files->first()->file_path);
    $exists = file_exists($filePath) ? "✅ Existe" : "❌ NO EXISTE";
    echo "   Estado: {$exists}\n";
    echo "   Ruta: {$filePath}\n";
    
    if ($files->count() > 1) {
        echo "   ⚠️ NOTA: Hay " . ($files->count() - 1) . " archivo(s) adicional(es) que NO se usarán\n";
        echo "   (PhpWord solo permite 1 imagen por placeholder)\n";
    }
    echo "\n";
}

echo "\n=== Mapeo de Placeholders ===\n";
$annexMapping = [
    'Certificado de Fumigación' => 'Anexo 1',
    'Factura de Insumos' => 'Anexo 2',
    'Registro Fotográfico' => 'Anexo 3',
    'Checklist Interno' => 'Anexo 4',
    'Memorando Aprobación' => 'Anexo 5',
];

foreach ($annexMapping as $annexName => $placeholder) {
    $found = $grouped->first(function($files, $annexId) use ($annexName) {
        return $files->first()->annex && $files->first()->annex->nombre === $annexName;
    });
    
    $status = $found ? "✅ Tiene archivo(s)" : "❌ Sin archivos";
    echo "{$placeholder}: {$annexName} - {$status}\n";
}
