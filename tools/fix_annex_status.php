<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$updated = DB::update("UPDATE annexes SET status = 'En revisión' WHERE status IS NULL OR status = '' OR status NOT IN ('En revisión','Aprobado','Obsoleto')");
echo "Rows updated: $updated\n";
