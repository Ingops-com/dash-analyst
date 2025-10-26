<?php
// Ejecutar: php tools/alter_company_annex_submissions.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("ALTER TABLE company_annex_submissions MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT");
    echo "ALTER TABLE company_annex_submissions applied\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
