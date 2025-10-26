<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
try {
    $row = DB::selectOne("SHOW CREATE TABLE company_annex_submissions");
    var_dump($row);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
