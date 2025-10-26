<?php
// Ejecutar: php tools/call_generate_direct2.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\UploadedFile as IlluminateUploadedFile;
use App\Http\Controllers\ProgramController;

try {
    $imagePath = __DIR__ . '/../public/images/logo.png';
    if (!file_exists($imagePath)) throw new Exception('Sample image not found: ' . $imagePath);

    // Create Illuminate UploadedFile directly with test=true
    $ilUploaded = new IlluminateUploadedFile($imagePath, 'logo.png', 'image/png', UPLOAD_ERR_OK, true);

    $post = [
        'company_id' => '1',
        'company_name' => 'Test Company',
        'company_nit' => '900',
        'company_address' => 'Calle Test',
        'company_activities' => 'Testing',
        'company_representative' => 'Niko',
        'anexos' => [ 0 => ['id' => '1'] ]
    ];

    $files = [
        'anexos' => [ 0 => ['archivo' => $ilUploaded] ]
    ];

    // Create request using factory and then attach files
    $request = IlluminateRequest::create('/programa/1/generate-pdf', 'POST', $post);
    $request->files->set('anexos', $files['anexos']);

    echo "Request allFiles():\n";
    var_export($request->allFiles());
    echo PHP_EOL;
        echo "Request all():\n";
        var_export($request->all());
        echo PHP_EOL;

    $controller = new ProgramController();
    $response = $controller->generatePdf($request, 1);

    if (is_object($response) && method_exists($response, 'getFile')) {
        echo "Generated file via controller: " . $response->getFile()->getPathname() . PHP_EOL;
    } else {
        echo "Controller returned: ";
        var_export($response);
        echo PHP_EOL;
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
