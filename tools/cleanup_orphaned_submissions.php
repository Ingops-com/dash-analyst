<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyAnnexSubmission;
use Illuminate\Support\Facades\Storage;

echo "Limpiando submissions huérfanas (archivos que no existen físicamente)...\n\n";

$allSubs = CompanyAnnexSubmission::whereNotNull('file_path')->get();
$deleted = 0;
$kept = 0;

foreach ($allSubs as $sub) {
    try {
        $absPath = Storage::disk('public')->path($sub->file_path);
    } catch (\Throwable $t) {
        $absPath = storage_path('app/public/' . ltrim($sub->file_path, '/'));
    }
    
    if (!file_exists($absPath)) {
        echo "✗ Eliminando submission huérfana: ID={$sub->id}, file={$sub->file_name}\n";
        $sub->delete();
        $deleted++;
    } else {
        $kept++;
    }
}

echo "\n✓ Proceso completado:\n";
echo "  - Submissions eliminadas (huérfanas): {$deleted}\n";
echo "  - Submissions mantenidas (válidas): {$kept}\n";
