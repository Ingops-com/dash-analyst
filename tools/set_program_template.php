<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Program;

$program = Program::find(1);
if (!$program) {
    echo "Program 1 not found\n";
    exit(1);
}
$program->template_type = 'plan_saneamiento';
$program->template_path = 'plantillas/planDeSaneamientoBasico/Plantilla.docx';
$program->save();

echo "Program 1 updated: template_type={$program->template_type}\n";