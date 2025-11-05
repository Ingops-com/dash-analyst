<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

$base = 'anexos';
$disk = Storage::disk('public');
if (!$disk->exists($base)) {
  echo "NO_DIR\n";
  exit(0);
}

$rii = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator(storage_path('app/public/'.$base), RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($rii as $file) {
  if ($file->isFile()) {
    echo $file->getPathname()."\n";
  }
}
