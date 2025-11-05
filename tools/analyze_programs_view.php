<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Análisis de Vista /programas ===\n\n";

echo "📊 ESTRUCTURA DE DATOS:\n\n";

// 1. Tabla programs
echo "1. Tabla 'programs':\n";
$programColumns = DB::select("DESCRIBE programs");
foreach ($programColumns as $col) {
    echo "   - {$col->Field} ({$col->Type}) " . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

$programCount = DB::table('programs')->count();
echo "   Total registros: {$programCount}\n\n";

// 2. Tabla annexes
echo "2. Tabla 'annexes':\n";
$annexColumns = DB::select("DESCRIBE annexes");
foreach ($annexColumns as $col) {
    echo "   - {$col->Field} ({$col->Type}) " . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

$annexCount = DB::table('annexes')->count();
echo "   Total registros: {$annexCount}\n\n";

// 3. Tabla program_annexes (relación muchos a muchos)
echo "3. Tabla 'program_annexes' (pivot):\n";
$pivotColumns = DB::select("DESCRIBE program_annexes");
foreach ($pivotColumns as $col) {
    echo "   - {$col->Field} ({$col->Type}) " . ($col->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

$pivotCount = DB::table('program_annexes')->count();
echo "   Total relaciones: {$pivotCount}\n\n";

// 4. Datos de ejemplo
echo "📄 DATOS DE EJEMPLO:\n\n";

echo "Programas:\n";
$programs = DB::table('programs')->limit(3)->get();
foreach ($programs as $p) {
    echo "   - ID {$p->id}: {$p->nombre} (Código: {$p->codigo}, Tipo: {$p->tipo})\n";
}
echo "\n";

echo "Anexos:\n";
$annexes = DB::table('annexes')->limit(5)->get();
foreach ($annexes as $a) {
    echo "   - ID {$a->id}: {$a->nombre} (Código: {$a->codigo_anexo}, Tipo: {$a->tipo})\n";
}
echo "\n";

echo "Relaciones Programa-Anexo:\n";
$relations = DB::table('program_annexes')
    ->join('programs', 'program_annexes.program_id', '=', 'programs.id')
    ->join('annexes', 'program_annexes.annex_id', '=', 'annexes.id')
    ->select('programs.nombre as programa', 'annexes.nombre as anexo')
    ->limit(10)
    ->get();
    
foreach ($relations as $r) {
    echo "   - {$r->programa} → {$r->anexo}\n";
}
