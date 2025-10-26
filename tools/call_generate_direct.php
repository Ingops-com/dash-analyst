<?php
// Ejecutar: php tools/call_generate_direct.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Http\Controllers\ProgramController;

try {
    $imagePath = __DIR__ . '/../public/images/logo.png';
    if (!file_exists($imagePath)) {
        throw new \Exception('Sample image not found: ' . $imagePath);
    }

    // Crear UploadedFile (último parámetro true = test, evita is_uploaded_file)
    // Fourth arg: upload error (UPLOAD_ERR_OK), fifth arg: $test=true to bypass is_uploaded_file
    $uploaded = new UploadedFile($imagePath, 'logo.png', 'image/png', UPLOAD_ERR_OK, true);

    // Preparar datos POST y FILES structure
    $post = [
        'company_id' => '1',
        'company_name' => 'Test Company',
        'company_nit' => '900123456',
        'company_address' => 'Calle Test 123',
        'company_activities' => 'Testing',
        'company_representative' => 'Niko Test',
        'anexos' => [
            0 => [ 'id' => '1' ]
        ]
    ];

    $files = [
        'anexos' => [
            0 => [ 'archivo' => $uploaded ]
        ]
    ];

    $symRequest = Symfony\Component\HttpFoundation\Request::create('/programa/1/generate-pdf', 'POST', $post, [], [], [], null);
    // Manually set files
    $symRequest->files->set('anexos', $files['anexos']);

    $request = Request::createFromBase($symRequest);

        // Debug: show files structure before calling controller
        echo "Request allFiles():\n";
        var_export($request->allFiles());
        echo PHP_EOL . "Request files->all():\n";
        var_export($request->files->all());
        echo PHP_EOL;

        // Now call controller
        $controller = new ProgramController();
        $response = $controller->generatePdf($request, 1);

    // Si es BinaryFileResponse o StreamedResponse, intentar inspeccionar
    if (is_object($response)) {
        if (method_exists($response, 'getFile')) {
            $file = $response->getFile();
            echo "Response contains file: " . $file->getPathname() . PHP_EOL;
        } elseif (method_exists($response, 'getContent')) {
            echo "Response content length: " . strlen($response->getContent()) . PHP_EOL;
        } else {
            echo "Controller returned object of class: " . get_class($response) . PHP_EOL;
            if (property_exists($response, 'original')) {
                var_dump($response->original);
            }
        }
    } else {
        echo "Controller returned: ";
        var_export($response);
        echo PHP_EOL;
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
