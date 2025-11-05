<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Estructura de annexes ===\n\n";

$columns = DB::select("DESCRIBE annexes");

foreach ($columns as $column) {
    echo "Campo: {$column->Field}\n";
    echo "  Tipo: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Key: {$column->Key}\n";
    echo "  Default: {$column->Default}\n";
    echo "  Extra: {$column->Extra}\n\n";
}

echo "\n=== SHOW CREATE TABLE ===\n\n";
$create = DB::select("SHOW CREATE TABLE annexes");
echo $create[0]->{'Create Table'} . "\n";
