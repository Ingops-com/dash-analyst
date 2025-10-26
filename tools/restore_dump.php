<?php
$root = __DIR__ . '/../';
$envPath = $root . '.env';
$dumpPath = $root . 'gsccazh_ (1).sql';

if (!file_exists($envPath)) {
    echo "Error: .env not found\n";
    exit(1);
}
if (!file_exists($dumpPath)) {
    echo "Error: SQL dump not found at: $dumpPath\n";
    exit(1);
}

// Parse .env simply
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (!str_contains($line, '=')) continue;
    [$k,$v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    $v = trim($v, "\"'");
    $env[$k] = $v;
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? 3306;
$db = $env['DB_DATABASE'] ?? null;
$user = $env['DB_USERNAME'] ?? null;
$pass = $env['DB_PASSWORD'] ?? '';

if (!$db || !$user) {
    echo "DB credentials missing in .env\n";
    exit(1);
}

echo "Restoring SQL dump to database '{$db}' on {$host}:{$port} as user {$user}\n";

$mysqli = new mysqli($host, $user, $pass, $db, (int)$port);
if ($mysqli->connect_errno) {
    echo "MySQL connect error: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

$sql = file_get_contents($dumpPath);
if ($sql === false) {
    echo "Failed to read SQL dump file.\n";
    exit(1);
}

// Increase timeout
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 60);
$mysqli->set_charset('utf8mb4');

// Execute multi query
if (!$mysqli->multi_query($sql)) {
    echo "Multi query failed: ({$mysqli->errno}) {$mysqli->error}\n";
    exit(1);
}

$counter = 0;
do {
    if ($res = $mysqli->store_result()) {
        $res->free();
    }
    $counter++;
    // Wait for next result
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->errno) {
    echo "Completed with errors: ({$mysqli->errno}) {$mysqli->error}\n";
    exit(1);
}

echo "SQL import finished successfully. Statements processed: {$counter}\n";
$mysqli->close();
return 0;
