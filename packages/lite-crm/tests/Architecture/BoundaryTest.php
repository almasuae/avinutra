<?php

declare(strict_types=1);

/*
 * The package must stay industry-neutral so it can be installed into any site.
 * Industry terms may appear only in presets/ (and in these tests).
 */

const LITE_CRM_SCANNED_DIRECTORIES = ['src', 'config', 'database', 'resources', 'routes'];

const LITE_CRM_FORBIDDEN_TERMS = [
    'avinutra' => '/avinutra/i',
    'poultry' => '/poultry/i',
    // "feed", but not the neutral word "feedback".
    'feed' => '/feed(?!back)/i',
    'methionine' => '/methionine/i',
    'lysine' => '/lysine/i',
    'broiler' => '/broiler/i',
    'hatchery' => '/hatcher(y|ies)/i',
    'livestock' => '/livestock/i',
    'chicken' => '/chicken/i',
    'amino acid' => '/amino[\s_-]?acid/i',
    'nutritionist' => '/nutritionist/i',
];

/**
 * @return list<string>
 */
function liteCrmScannedFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach (LITE_CRM_SCANNED_DIRECTORIES as $directory) {
        $path = $root.DIRECTORY_SEPARATOR.$directory;

        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

it('scans the package directories', function (): void {
    expect(liteCrmScannedFiles())->not->toBeEmpty();
});

it('contains no industry or host terms outside presets', function (): void {
    $root = dirname(__DIR__, 2);
    $violations = [];

    foreach (liteCrmScannedFiles() as $file) {
        $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));

        // File and directory names are checked as well as contents.
        $haystacks = ['path' => $relative, 'contents' => (string) file_get_contents($file)];

        foreach (LITE_CRM_FORBIDDEN_TERMS as $term => $pattern) {
            foreach ($haystacks as $where => $haystack) {
                if (preg_match($pattern, $haystack) === 1) {
                    $violations[] = "{$relative}: \"{$term}\" found in {$where}";
                }
            }
        }
    }

    expect($violations)->toBe([], "Industry terms belong in presets/ or the host app:\n".implode("\n", $violations));
});

it('detects a forbidden term', function (string $sample): void {
    $matched = array_filter(
        LITE_CRM_FORBIDDEN_TERMS,
        fn (string $pattern): bool => preg_match($pattern, $sample) === 1,
    );

    expect($matched)->not->toBeEmpty();
})->with(['AviNutra', 'feed mill', 'FeedMill', 'feed_additives', 'DL-Methionine', 'poultry', 'amino-acid']);

it('allows neutral words that contain a forbidden term', function (): void {
    $matched = array_filter(
        LITE_CRM_FORBIDDEN_TERMS,
        fn (string $pattern): bool => preg_match($pattern, 'customer feedback') === 1,
    );

    expect($matched)->toBeEmpty();
});

arch('the package never depends on the host application')
    ->expect('LiteCrm')
    ->not->toUse(['App', 'Database\\Seeders', 'Database\\Factories']);

arch('package classes use strict types')
    ->expect('LiteCrm')
    ->toUseStrictTypes();

arch('no debugging calls are left in the package')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
