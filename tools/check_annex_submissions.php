<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyAnnexSubmission;
use Illuminate\Support\Facades\Storage;

$companyId = $argv[1] ?? 1;
$programId = $argv[2] ?? 1;
$annexId = $argv[3] ?? 1;

echo "Buscando submissions para company_id={$companyId}, program_id={$programId}, annex_id={$annexId}\n\n";

$subs = CompanyAnnexSubmission::where('company_id', $companyId)
    ->where('program_id', $programId)
    ->where('annex_id', $annexId)
    ->whereNotNull('file_path')
    ->orderBy('updated_at', 'desc')
    ->get();

echo "Total submissions en BD: " . $subs->count() . "\n\n";

foreach ($subs as $s) {
    try {
        $absPath = Storage::disk('public')->path($s->file_path);
    } catch (\Throwable $t) {
        $absPath = storage_path('app/public/' . ltrim($s->file_path, '/'));
    }
    
    $exists = file_exists($absPath);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    
    echo sprintf(
        "%s | ID: %d | Updated: %s\n      File: %s\n      Path: %s\n\n",
        $status,
        $s->id,
        $s->updated_at,
        $s->file_name,
        $absPath
    );
}
