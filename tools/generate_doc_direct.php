<?php
// Ejecutar: php tools/generate_doc_direct.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Program;
use App\Models\Annex;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

try {
    $program = Program::find(1);
    if (!$program) throw new Exception('Program 1 not found');

    $templatePath = storage_path('plantillas/planDeSaneamientoBasico/Plantilla.docx');
    if (!file_exists($templatePath)) throw new Exception('Template not found at ' . $templatePath);

    $templateProcessor = new TemplateProcessor($templatePath);

    $variables = [
        'Programa' => $program->nombre,
        'Empresa' => 'Test Company',
        'NIT' => '900',
        'Direccion' => 'Calle Test',
        'ActividadEmpresa' => 'Testing',
        'RepresentanteLegal' => 'Niko',
        'Fecha' => date('d/m/Y'),
        'Version' => $program->version ?? '1.0',
        'Codigo' => $program->codigo ?? '-'
    ];
    foreach ($variables as $k => $v) {
        $templateProcessor->setValue($k, $v);
    }

    // Map first annex to image
    $annexMapping = [
        'Certificado de Fumigación' => 'Anexo 1',
        'Factura de Insumos' => 'Anexo 2',
        'Registro Fotográfico' => 'Anexo 3',
        'Checklist Interno' => 'Anexo 4',
        'Memorando Aprobación' => 'Anexo 5',
    ];

    // Use sample image
    $image = __DIR__ . '/../public/images/logo.png';
    if (!file_exists($image)) throw new Exception('Sample image not found: ' . $image);

    // Try to set image for Anexo 1
    $templateProcessor->setImageValue('Anexo 1', ['path' => $image, 'width' => 400, 'ratio' => true]);

    $tempDir = storage_path('app/temp');
    if (!File::isDirectory($tempDir)) File::makeDirectory($tempDir, 0755, true);
    $out = $tempDir . '/test_generated_' . uniqid() . '.docx';
    $templateProcessor->saveAs($out);

    echo "Generated file: " . $out . PHP_EOL;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
