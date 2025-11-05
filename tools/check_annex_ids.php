<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== IDs en annexes ===\n\n";

$annexes = DB::select('SELECT id, nombre, codigo_anexo FROM annexes ORDER BY id');

foreach ($annexes as $annex) {
    echo "ID: {$annex->id} - {$annex->nombre} ({$annex->codigo_anexo})\n";
}

echo "\n=== Verificar duplicados ===\n";
$duplicates = DB::select('SELECT id, COUNT(*) as count FROM annexes GROUP BY id HAVING count > 1');

if (empty($duplicates)) {
    echo "No hay IDs duplicados\n";
} else {
    echo "IDs duplicados encontrados:\n";
    foreach ($duplicates as $dup) {
        echo "ID {$dup->id} aparece {$dup->count} veces\n";
    }
}
