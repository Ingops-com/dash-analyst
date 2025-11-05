<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Create temp file
$tmp = tempnam(sys_get_temp_dir(), 'upl');
file_put_contents($tmp, str_repeat('A', 1024));
$uf = new UploadedFile($tmp, 'dummy.png', 'image/png', null, true);

$dir = 'anexos/company_1/program_1';
$name = 'test_' . uniqid() . '.png';

$rel = $uf->storeAs($dir, $name, 'public');
$exists = Storage::disk('public')->exists($rel);
$abs = Storage::disk('public')->path($rel);

echo "rel: $rel\n";
echo "exists: " . ($exists ? 'YES' : 'NO') . "\n";
echo "abs: $abs\n";
if ($exists) {
  echo "size: " . filesize($abs) . "\n";
  Storage::disk('public')->delete($rel);
}
@unlink($tmp);
