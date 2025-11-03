<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompanyAnnexSubmission;
use App\Models\Annex;

echo "=== Archivos en company_annex_submissions para company_id=1, program_id=1 ===\n\n";

$submissions = CompanyAnnexSubmission::where('company_id', 1)
    ->where('program_id', 1)
    ->get();

$grouped = $submissions->groupBy('annex_id');

foreach ($grouped as $annexId => $files) {
    $annex = Annex::find($annexId);
    $annexName = $annex ? $annex->nombre : "Annex ID $annexId (not found)";
    
    echo "$annexName ($annexId): " . $files->count() . " archivos\n";
    
    // Mostrar los primeros 3 archivos como ejemplo
    $sampleFiles = $files->take(3);
    foreach ($sampleFiles as $file) {
        echo "  - {$file->file_name} ({$file->status}) - creado: {$file->created_at}\n";
    }
    
    if ($files->count() > 3) {
        echo "  ... y " . ($files->count() - 3) . " archivos más\n";
    }
    echo "\n";
}

echo "Total de registros: " . $submissions->count() . "\n";
