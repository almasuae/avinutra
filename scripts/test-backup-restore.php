<?php

declare(strict_types=1);

/*
 * composer test:backup — proves that a backup restores (v5 §G2, §F5).
 *
 *  1. builds a source database (migrations + seeders) on the local test MariaDB;
 *  2. runs the real `php artisan backup:run` into a temporary folder;
 *  3. unzips the newest backup and loads its SQL dump into a second database;
 *  4. compares the row count of every table, and checks the uploaded files are in the zip.
 *
 * Uses the same local MariaDB as composer test:mariadb (portable server started and
 * stopped if needed). The databases avinutra_backup_source and avinutra_backup_restore
 * are dropped and recreated on every run. Never point this at a server with real data.
 */

require __DIR__.'/lib/mariadb.php';

$root = dirname(__DIR__);
$settings = mariadbSettings();
[$pdo, $startedHere] = mariadbEnsureServer($settings);
$isWindows = PHP_OS_FAMILY === 'Windows';
$client = $settings['bin'].DIRECTORY_SEPARATOR.($isWindows ? 'mariadb.exe' : 'mariadb');
$client = is_file($client) ? $client : 'mariadb';
$source = 'avinutra_backup_source';
$target = 'avinutra_backup_restore';
$workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'avinutra-backup-test-'.getmypid();
$checkFile = $root.'/storage/app/private/backup-restore-check-'.getmypid().'.txt';
$failures = [];

$finish = function (int $code) use ($pdo, $startedHere, $checkFile, $workDir): never {
    @unlink($checkFile);
    if (is_dir($workDir)) {
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($workDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($workDir);
    }
    if ($startedHere) {
        echo "Stopping MariaDB...\n";
        $pdo->exec('SHUTDOWN');
    }
    exit($code);
};

echo 'MariaDB '.$pdo->query('SELECT VERSION()')->fetchColumn()."\n";

foreach ([$source, $target] as $database) {
    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

$env = [
    'APP_ENV' => 'local',
    'DB_CONNECTION' => 'mariadb',
    'DB_HOST' => $settings['host'],
    'DB_PORT' => (string) $settings['port'],
    'DB_DATABASE' => $source,
    'DB_USERNAME' => $settings['username'],
    'DB_PASSWORD' => $settings['password'],
    'DB_URL' => '',
    'DB_DUMP_BINARY_PATH' => is_dir($settings['bin']) ? $settings['bin'] : '',
    'BACKUP_PATH' => $workDir,
    'BACKUP_ARCHIVE_PASSWORD' => '',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
];

echo "\n1. Building the source database (migrations + seeders)...\n";
[$code, $output] = runWithEnv([PHP_BINARY, 'artisan', 'migrate', '--force', '--seed', '--no-interaction'], $env, $root);
if ($code !== 0) {
    fwrite(STDERR, $output);
    $finish(1);
}

file_put_contents($checkFile, 'backup restore check '.date(DATE_ATOM));

echo "2. Running php artisan backup:run...\n";
[$code, $output] = runWithEnv([PHP_BINARY, 'artisan', 'backup:run', '--disable-notifications', '--no-interaction'], $env, $root);
echo preg_replace('/^/m', '   ', trim($output))."\n";
if ($code !== 0) {
    fwrite(STDERR, "backup:run failed.\n");
    $finish(1);
}

$zips = glob($workDir.'/*/*.zip') ?: [];
rsort($zips);
if ($zips === []) {
    fwrite(STDERR, "No backup zip was written to {$workDir}.\n");
    $finish(1);
}

echo '3. Restoring '.basename($zips[0])." into {$target}...\n";
$zip = new ZipArchive;
$zip->open($zips[0]);
$extract = $workDir.'/extract';
$zip->extractTo($extract);
$names = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $names[] = (string) $zip->getNameIndex($i);
}
$zip->close();

$dumps = glob($extract.'/db-dumps/*.sql') ?: [];
if ($dumps === []) {
    $failures[] = 'The backup contains no database dump.';
} else {
    $restoreEnv = $settings['password'] !== '' ? ['MYSQL_PWD' => $settings['password']] : [];
    [$code, $output] = runWithEnv([$client, '-h', $settings['host'], '-P', (string) $settings['port'], '-u', $settings['username'], $target], $restoreEnv, $root, $dumps[0]);
    if ($code !== 0) {
        $failures[] = 'Loading the dump failed: '.trim($output);
    }
}

$hasCheckFile = (bool) array_filter($names, fn (string $name): bool => str_ends_with($name, basename($checkFile)));
if (! $hasCheckFile) {
    $failures[] = 'The uploaded-files folder (storage/app/private) is missing from the backup.';
}

echo "4. Comparing tables...\n";
$tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$source}' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
$rows = 0;
foreach ($tables as $table) {
    $expected = (int) $pdo->query("SELECT COUNT(*) FROM `{$source}`.`{$table}`")->fetchColumn();
    try {
        $actual = (int) $pdo->query("SELECT COUNT(*) FROM `{$target}`.`{$table}`")->fetchColumn();
    } catch (PDOException) {
        $failures[] = "Table {$table} is missing after the restore.";

        continue;
    }
    if ($expected !== $actual) {
        $failures[] = "Table {$table}: {$expected} rows before, {$actual} after the restore.";
    }
    $rows += $expected;
}

if ($failures !== []) {
    fwrite(STDERR, "\nBACKUP RESTORE FAILED:\n  ".implode("\n  ", $failures)."\n");
    $finish(1);
}

echo "\nBackup restored: ".count($tables)." tables and {$rows} rows match; uploaded files are included.\n";
$finish(0);
