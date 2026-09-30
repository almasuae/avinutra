<?php

declare(strict_types=1);

/*
 * composer test:browser — browser checks against the local site (local database).
 *
 * Starts its own PHP development server, runs scripts/check-overflow.mjs (every
 * public page must fit 390 px), then stops only the server it started.
 * Needs Node 20.10+ and Edge or Chrome (BROWSER_PATH to override).
 */

$root = dirname(__DIR__);
$port = (int) (getenv('BROWSER_TEST_PORT') ?: 8766);
$php = PHP_BINARY;
$node = getenv('NODE_BINARY') ?: 'node';

$server = proc_open(
    [$php, '-S', "127.0.0.1:{$port}", $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
    // The server log is rewritten on every run (it used to grow without limit).
    [['file', 'php://stdin', 'r'], ['file', $root.'/storage/logs/browser-test-server.log', 'w'], ['file', $root.'/storage/logs/browser-test-server.log', 'w']],
    $pipes,
    $root.'/public',
);

if (! is_resource($server)) {
    fwrite(STDERR, "Could not start the PHP server.\n");
    exit(2);
}

// Stop only the server this script started, also when the script ends early (an error or exit).
$stopServer = function () use (&$server): void {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
};
register_shutdown_function($stopServer);

// Wait for the server to answer.
for ($i = 0; $i < 50; $i++) {
    if (@file_get_contents("http://127.0.0.1:{$port}/") !== false) {
        break;
    }
    usleep(200_000);
}

passthru(escapeshellarg($node).' --experimental-websocket '.escapeshellarg($root.'/scripts/check-overflow.mjs').' '.escapeshellarg("http://127.0.0.1:{$port}"), $exitCode);

$stopServer();

exit($exitCode);
