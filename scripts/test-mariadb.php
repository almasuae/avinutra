<?php

declare(strict_types=1);

/*
 * Runs the host and package test suites against MariaDB (production parity).
 *
 *   composer test:mariadb
 *
 * Uses a local MariaDB 11.4 on 127.0.0.1. If nothing is listening on the port, the
 * portable server in MARIADB_HOME is started for the run and stopped afterwards.
 *
 * Environment (all optional):
 *   MARIADB_HOME       portable MariaDB directory (default: ~/.local/mariadb114)
 *   TEST_DB_HOST       default 127.0.0.1
 *   TEST_DB_PORT       default 3307
 *   TEST_DB_USERNAME   default root
 *   TEST_DB_PASSWORD   default empty
 *
 * The test databases (avinutra_test, lite_crm_test) are dropped and recreated on
 * every run. Never point this at a server that holds real data.
 */

$root = dirname(__DIR__);
$home = getenv('USERPROFILE') ?: getenv('HOME') ?: '';
$mariadbHome = getenv('MARIADB_HOME') ?: $home.DIRECTORY_SEPARATOR.'.local'.DIRECTORY_SEPARATOR.'mariadb114';
$host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('TEST_DB_PORT') ?: 3307);
$username = getenv('TEST_DB_USERNAME') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: '';

$suites = [
    'host' => ['database' => 'avinutra_test', 'command' => [PHP_BINARY, 'artisan', 'test']],
    'package' => ['database' => 'lite_crm_test', 'command' => [PHP_BINARY, 'vendor/bin/pest', '--configuration', 'packages/lite-crm/phpunit.xml']],
];

function connect(string $host, int $port, string $username, string $password): ?PDO
{
    try {
        return new PDO("mysql:host={$host};port={$port}", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
    } catch (PDOException) {
        return null;
    }
}

$pdo = connect($host, $port, $username, $password);
$startedHere = false;

if ($pdo === null) {
    $isWindows = PHP_OS_FAMILY === 'Windows';
    $server = $mariadbHome.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.($isWindows ? 'mariadbd.exe' : 'mariadbd');
    $defaults = $mariadbHome.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'my.ini';

    if (! is_file($server) || ! is_file($defaults)) {
        fwrite(STDERR, "No MariaDB on {$host}:{$port}, and no portable server at {$mariadbHome}.\n");
        exit(1);
    }

    echo "Starting MariaDB from {$mariadbHome} on port {$port}...\n";

    $command = $isWindows
        ? 'start "" /B "'.$server.'" --defaults-file="'.$defaults.'" --console > NUL 2>&1'
        : escapeshellarg($server).' --defaults-file='.escapeshellarg($defaults).' > /dev/null 2>&1 &';
    pclose(popen($command, 'r'));

    for ($i = 0; $i < 60 && $pdo === null; $i++) {
        usleep(500_000);
        $pdo = connect($host, $port, $username, $password);
    }

    if ($pdo === null) {
        fwrite(STDERR, "MariaDB did not start within 30 seconds.\n");
        exit(1);
    }

    $startedHere = true;
}

echo 'MariaDB '.$pdo->query('SELECT VERSION()')->fetchColumn()."\n";

$failed = [];

foreach ($suites as $name => $suite) {
    $pdo->exec("DROP DATABASE IF EXISTS `{$suite['database']}`");
    $pdo->exec("CREATE DATABASE `{$suite['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $env = array_merge(getenv(), [
        'DB_CONNECTION' => 'mariadb',
        'DB_HOST' => $host,
        'DB_PORT' => (string) $port,
        'DB_DATABASE' => $suite['database'],
        'DB_USERNAME' => $username,
        'DB_PASSWORD' => $password,
        'DB_URL' => '',
        // Own compiled-view folder: never the local site's storage/framework/views, and
        // never shared with a SQLite run at the same time (Windows rename collisions).
        'VIEW_COMPILED_PATH' => $root.'/storage/framework/testing/views-mariadb-'.strtolower((string) $name),
    ]);

    echo "\n== {$name} tests on MariaDB ({$suite['database']}) ==\n";

    // Capture the output and print it in one piece, so suites never interleave.
    $process = proc_open($suite['command'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $root, $env);

    if (! is_resource($process)) {
        $failed[] = $name;

        continue;
    }

    fclose($pipes[0]);
    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $exitCode = proc_close($process);

    echo "\n-- {$name}: ".($exitCode === 0 ? 'PASSED' : "FAILED (exit {$exitCode})")." --\n";

    if ($exitCode !== 0) {
        $failed[] = $name;
    }
}

if ($startedHere) {
    echo "\nStopping MariaDB...\n";
    $pdo->exec('SHUTDOWN');
}

if ($failed !== []) {
    fwrite(STDERR, "\nFailed on MariaDB: ".implode(', ', $failed)."\n");
    exit(1);
}

echo "\nAll suites passed on MariaDB.\n";
