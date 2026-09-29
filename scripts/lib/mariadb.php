<?php

declare(strict_types=1);

/*
 * Shared by the MariaDB test scripts: connect to the local test server, starting the
 * portable MariaDB in MARIADB_HOME if nothing is listening. A server started here is
 * stopped with SQL SHUTDOWN (never by killing processes by name).
 */

/**
 * @return array{host: string, port: int, username: string, password: string, home: string, bin: string}
 */
function mariadbSettings(): array
{
    $home = getenv('USERPROFILE') ?: getenv('HOME') ?: '';
    $mariadbHome = getenv('MARIADB_HOME') ?: $home.DIRECTORY_SEPARATOR.'.local'.DIRECTORY_SEPARATOR.'mariadb114';

    return [
        'host' => getenv('TEST_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('TEST_DB_PORT') ?: 3307),
        'username' => getenv('TEST_DB_USERNAME') ?: 'root',
        'password' => getenv('TEST_DB_PASSWORD') ?: '',
        'home' => $mariadbHome,
        'bin' => $mariadbHome.DIRECTORY_SEPARATOR.'bin',
    ];
}

function mariadbConnect(string $host, int $port, string $username, string $password): ?PDO
{
    try {
        return new PDO("mysql:host={$host};port={$port}", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
    } catch (PDOException) {
        return null;
    }
}

/**
 * @param  array{host: string, port: int, username: string, password: string, home: string, bin: string}  $settings
 * @return array{0: PDO, 1: bool} the connection, and whether the server was started here
 */
function mariadbEnsureServer(array $settings): array
{
    $pdo = mariadbConnect($settings['host'], $settings['port'], $settings['username'], $settings['password']);

    if ($pdo !== null) {
        return [$pdo, false];
    }

    $isWindows = PHP_OS_FAMILY === 'Windows';
    $server = $settings['bin'].DIRECTORY_SEPARATOR.($isWindows ? 'mariadbd.exe' : 'mariadbd');
    $defaults = $settings['home'].DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'my.ini';

    if (! is_file($server) || ! is_file($defaults)) {
        fwrite(STDERR, "No MariaDB on {$settings['host']}:{$settings['port']}, and no portable server at {$settings['home']}.\n");
        exit(1);
    }

    echo "Starting MariaDB from {$settings['home']} on port {$settings['port']}...\n";

    $command = $isWindows
        ? 'start "" /B "'.$server.'" --defaults-file="'.$defaults.'" --console > NUL 2>&1'
        : escapeshellarg($server).' --defaults-file='.escapeshellarg($defaults).' > /dev/null 2>&1 &';
    pclose(popen($command, 'r'));

    for ($i = 0; $i < 60 && $pdo === null; $i++) {
        usleep(500_000);
        $pdo = mariadbConnect($settings['host'], $settings['port'], $settings['username'], $settings['password']);
    }

    if ($pdo === null) {
        fwrite(STDERR, "MariaDB did not start within 30 seconds.\n");
        exit(1);
    }

    return [$pdo, true];
}

/**
 * Runs a command with extra environment variables; returns [exit code, output].
 *
 * @param  list<string>  $command
 * @param  array<string, string>  $env
 * @return array{0: int, 1: string}
 */
function runWithEnv(array $command, array $env, string $cwd, ?string $stdinFile = null): array
{
    $descriptors = [0 => $stdinFile !== null ? ['file', $stdinFile, 'r'] : ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]];
    $process = proc_open($command, $descriptors, $pipes, $cwd, array_merge(getenv(), $env));

    if (! is_resource($process)) {
        return [1, 'Could not start: '.implode(' ', $command)];
    }

    if ($stdinFile === null) {
        fclose($pipes[0]);
    }
    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($process), $output];
}
