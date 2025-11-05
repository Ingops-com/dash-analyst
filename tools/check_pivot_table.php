<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Estructura de program_annexes ===\n\n";

$columns = DB::select("DESCRIBE program_annexes");

foreach ($columns as $column) {
    echo "Campo: {$column->Field}\n";
    echo "  Tipo: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Key: {$column->Key}\n";
    echo "  Default: {$column->Default}\n";
    echo "  Extra: {$column->Extra}\n\n";
}

echo "\n=== Datos actuales en program_annexes ===\n\n";
$data = DB::table('program_annexes')->get();
foreach ($data as $row) {
    print_r($row);
}
