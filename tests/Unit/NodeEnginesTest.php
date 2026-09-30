<?php

declare(strict_types=1);

use Composer\Semver\Semver;

/*
 * deploy.sh runs `npm ci` on the server, which has Node 20.20.2. Every package in the
 * root lock file must accept that version, or npm prints EBADENGINE warnings (as
 * concurrently 10.x did). Tools that need a newer Node live in their own folder
 * (e.g. tools/lighthouse), outside the root package.json.
 */

const SERVER_NODE_VERSION = '20.20.2';

it('installs only npm packages that support the server\'s Node version', function (): void {
    $lock = json_decode((string) file_get_contents(__DIR__.'/../../package-lock.json'), true, flags: JSON_THROW_ON_ERROR);
    $unsupported = [];

    foreach ($lock['packages'] as $path => $package) {
        $range = $package['engines']['node'] ?? null;

        if ($path === '' || ! is_string($range) || ($package['optional'] ?? false) && ($package['os'] ?? null) !== null) {
            continue; // the root, packages without a Node range, and platform-specific optional binaries
        }

        if (! Semver::satisfies(SERVER_NODE_VERSION, $range)) {
            $unsupported[] = "{$path} ({$range})";
        }
    }

    expect($unsupported)->toBe([]);
});

it('requires a Node version the server has', function (): void {
    $package = json_decode((string) file_get_contents(__DIR__.'/../../package.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(Semver::satisfies(SERVER_NODE_VERSION, $package['engines']['node']))->toBeTrue()
        ->and($package['devDependencies'] ?? [])->not->toHaveKey('concurrently')
        ->and($package['devDependencies'] ?? [])->not->toHaveKey('lighthouse');
});
