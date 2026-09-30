<?php

declare(strict_types=1);

/*
 * An empty line in .env ("KEY=") must behave like a missing key: env() returns ""
 * rather than the default, which once gave the backups disk an empty root
 * ("Unable to create a directory at ."). Every key that .env.example leaves empty
 * or commented out, and every key of our own (BACKUP_*, SECURITY_*, LITE_CRM_*,
 * DB_DUMP_*), must give the same configuration whether it is empty or absent.
 */

// The dataset is built before the application boots, so plain paths are used.
const ENV_FALLBACK_ROOT = __DIR__.'/../..';

/**
 * @return list<string>
 */
function envExampleKeysToCheck(): array
{
    $example = (string) file_get_contents(ENV_FALLBACK_ROOT.'/.env.example');
    preg_match_all('/^#?\s*([A-Z][A-Z0-9_]*)=\s*$/m', $example, $empty);
    preg_match_all('/^#\s*([A-Z][A-Z0-9_]*)=/m', $example, $commented);

    $configCode = '';
    foreach (envFallbackConfigFiles() as $file) {
        $configCode .= file_get_contents($file);
    }
    preg_match_all("/env\\('((?:BACKUP|SECURITY|LITE_CRM|DB_DUMP)_[A-Z0-9_]*)'/", $configCode, $own);

    return array_values(array_unique(array_merge($empty[1], $commented[1], $own[1])));
}

/**
 * @return list<string>
 */
function envFallbackConfigFiles(): array
{
    return array_merge(glob(ENV_FALLBACK_ROOT.'/config/*.php') ?: [], [ENV_FALLBACK_ROOT.'/packages/lite-crm/config/lite-crm.php']);
}

/**
 * The whole configuration, with the key set to $value (null = absent).
 *
 * @return array<string, mixed>
 */
function configWithEnv(string $key, ?string $value): array
{
    $saved = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];

    unset($_ENV[$key], $_SERVER[$key]);
    putenv($key);

    if ($value !== null) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    try {
        $config = [];
        foreach (envFallbackConfigFiles() as $file) {
            $config[$file] = require $file;
        }
    } finally {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
        if ($saved[0] !== null) {
            $_ENV[$key] = $saved[0];
        }
        if ($saved[1] !== null) {
            $_SERVER[$key] = $saved[1];
        }
        if ($saved[2] !== false) {
            putenv("{$key}={$saved[2]}");
        }
    }

    // "" and null both mean "nothing": credentials without a default may be either.
    array_walk_recursive($config, function (mixed &$item): void {
        $item = $item === '' ? null : $item;
    });

    return $config;
}

it('falls back to the default when a key is empty in .env', function (string $key): void {
    expect(configWithEnv($key, ''))->toEqual(configWithEnv($key, null));
})->with(fn (): array => envExampleKeysToCheck());

it('keeps backups in storage/app/backups when BACKUP_PATH is empty', function (): void {
    $root = configWithEnv('BACKUP_PATH', '')[ENV_FALLBACK_ROOT.'/config/filesystems.php']['disks']['backups']['root'];

    expect($root)->toBe(storage_path('app/backups'));
});
