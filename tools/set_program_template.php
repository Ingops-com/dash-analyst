<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Program;

echo "=== Actualizando plantillas de programas ===\n\n";

// Programa 1: Plan de Saneamiento Básico
$program = Program::find(1);
if ($program) {
    $program->template_path = 'planDeSaneamientoBasico/Plantilla.docx';
    $program->description = 'Documento PSB (Plan de Saneamiento Básico) con anexos de fumigación, limpieza y control de plagas';
    $program->save();
    echo "✓ Programa 1 actualizado: {$program->nombre}\n";
    echo "  Template: {$program->template_path}\n\n";
}

// Actualizar otros programas si es necesario
$program2 = Program::find(2);
if ($program2) {
    echo "⚠ Programa 2 ({$program2->nombre}) sin plantilla configurada\n";
    echo "  Puedes agregar la plantilla manualmente o crear una nueva\n\n";
}

$program3 = Program::find(3);
if ($program3) {
    echo "⚠ Programa 3 ({$program3->nombre}) sin plantilla configurada\n";
    echo "  Puedes agregar la plantilla manualmente o crear una nueva\n\n";
}

echo "\nPara configurar plantillas para otros programas:\n";
echo "UPDATE programs SET template_path = 'nombreCarpeta/archivo.docx' WHERE id = X;\n";
