<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

$path = 'anexos/test_write_' . uniqid() . '.txt';
$data = 'hello ' . date('c');

$ok = Storage::disk('public')->put($path, $data);
$abs = Storage::disk('public')->path($path);
$exists = Storage::disk('public')->exists($path);

echo "put: " . ($ok ? 'OK' : 'FAIL') . "\n";
echo "abs: $abs\n";
echo "exists: " . ($exists ? 'YES' : 'NO') . "\n";
if ($exists) {
  echo "size: " . filesize($abs) . " bytes\n";
  // cleanup
  Storage::disk('public')->delete($path);
  echo "deleted: " . (Storage::disk('public')->exists($path) ? 'NO' : 'YES') . "\n";
}
