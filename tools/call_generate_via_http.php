<?php
// Ejecutar: php tools/call_generate_via_http.php <program_id> <company_id>
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Company;
use App\Models\Program;

$programId = (int)($argv[1] ?? 1);
$companyId = (int)($argv[2] ?? 1);

$program = Program::find($programId);
$company = Company::find($companyId);
if (!$program || !$company) {
    echo "Program o Company no encontrados.\n";
    exit(1);
}

// Construir payload similar al frontend (mínimo viable)
$payload = [
    'company_id' => $company->id,
    'company_name' => $company->nombre,
    'company_nit' => $company->nit_empresa,
    'company_address' => $company->direccion,
    'company_activities' => $company->actividades,
    'company_representative' => $company->representante_legal,
];

// Simular autenticación (tomar primer usuario como contexto)
$user = \App\Models\User::first();
if ($user) {
    Auth::login($user);
}

// Iniciar sesión y CSRF
Session::start();
$token = csrf_token();
$payload['_token'] = $token;

// Crear request autenticado
$request = \Illuminate\Http\Request::create("/programa/{$programId}/generate-pdf", 'POST', $payload);
$request->setLaravelSession(Session::driver());
$request->headers->set('X-CSRF-TOKEN', $token);
$response = $app->handle($request);

$status = $response->getStatusCode();
$ctype = $response->headers->get('Content-Type');
$fileOut = null;
if ($status === 200 && str_contains((string)$ctype, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
    $temp = storage_path('app/temp/generated_via_http_' . uniqid() . '.docx');
    file_put_contents($temp, $response->getContent());
    $fileOut = $temp;
}

echo "Status: {$status}\nContent-Type: {$ctype}\n";
if ($fileOut) {
    echo "Archivo guardado: {$fileOut}\n";
} else {
    echo "Respuesta cuerpo (truncado): " . substr($response->getContent(), 0, 400) . "\n";
}
