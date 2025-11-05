<?php
$path = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($path)) {
    echo "No log file found"; exit;
}
$lines = file($path);
$tail = array_slice($lines, -200);
foreach ($tail as $line) echo $line;