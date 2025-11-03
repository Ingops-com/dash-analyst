php artisan tinker --execute="DB::table('companies')->where('id',1)->update(['revisado_por'=>'ING. Gloria Marcela Cabrejo Moreno','aprobado_por'=>'Ing Gloria Cabrejo']);"<?php
$root = __DIR__ . '/../';
$envPath = $root . '.env';

// Allow passing a dump path as first CLI arg. Fallback to files in project root.
$cliArgPath = $argv[1] ?? null;
$freshFlag = in_array('--fresh', $argv, true);
$candidates = [];
if ($cliArgPath) {
    $candidates[] = $cliArgPath;
}
$candidates[] = $root . 'gsccazh_ (2).sql';
$candidates[] = $root . 'gsccazh_ (1).sql';

$dumpPath = null;
foreach ($candidates as $c) {
    if (file_exists($c)) { $dumpPath = $c; break; }
}

if (!file_exists($envPath)) {
    echo "Error: .env not found\n";
    exit(1);
}
if (!$dumpPath) {
    echo "Error: SQL dump not found. Place your dump at project root as 'gsccazh_ (2).sql' or pass a full path as first argument.\n";
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

echo "Restoring SQL dump from: {$dumpPath}\n";
echo "Target database: '{$db}' on {$host}:{$port} as user {$user}\n";

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

// Optionally drop all tables first if --fresh flag is present
if ($freshFlag) {
    echo "--fresh flag detected: dropping existing tables...\n";
    $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
    if ($res = $mysqli->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')) {
        while ($row = $res->fetch_array()) {
            $table = $row[0];
            $mysqli->query("DROP TABLE IF EXISTS `{$table}`");
        }
        $res->free();
    }
    $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
}

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
