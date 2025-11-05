<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Limpiando duplicados en annexes ===\n\n";

// Get all annexes
$annexes = DB::select('SELECT * FROM annexes ORDER BY id, created_at');

echo "Total registros antes: " . count($annexes) . "\n\n";

// Keep track of seen IDs
$seenIds = [];
$toDelete = [];

foreach ($annexes as $annex) {
    if (in_array($annex->id, $seenIds)) {
        echo "Duplicado encontrado: ID {$annex->id} - {$annex->nombre}\n";
        $toDelete[] = $annex;
    } else {
        $seenIds[] = $annex->id;
        echo "Conservando: ID {$annex->id} - {$annex->nombre}\n";
    }
}

if (!empty($toDelete)) {
    echo "\nEliminando " . count($toDelete) . " duplicados...\n\n";
    
    // Delete duplicates one by one by matching all fields
    foreach ($toDelete as $dup) {
        DB::delete(
            "DELETE FROM annexes WHERE id = ? AND nombre = ? AND codigo_anexo = ? LIMIT 1",
            [$dup->id, $dup->nombre, $dup->codigo_anexo]
        );
        echo "Eliminado duplicado: ID {$dup->id}\n";
    }
    
    echo "\n=== Verificación final ===\n";
    $remaining = DB::select('SELECT id, COUNT(*) as count FROM annexes GROUP BY id HAVING count > 1');
    
    if (empty($remaining)) {
        echo "✓ No quedan duplicados\n";
    } else {
        echo "✗ Aún hay duplicados:\n";
        foreach ($remaining as $r) {
            echo "  ID {$r->id}: {$r->count} veces\n";
        }
    }
} else {
    echo "\nNo hay duplicados para eliminar\n";
}
