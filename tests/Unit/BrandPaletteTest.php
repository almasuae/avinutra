<?php

declare(strict_types=1);

use App\Support\BrandPalette;

it('keeps the PHP palette in sync with the CSS theme tokens', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    foreach (BrandPalette::TOKENS as $token => $hex) {
        expect($css)->toMatch('/--color-'.preg_quote($token, '/').':\s*'.preg_quote($hex, '/').';/i');
    }
});

it('meets WCAG AA for every text and background pair the site uses', function (string $foreground, string $background, float $minimum): void {
    expect(BrandPalette::contrast($foreground, $background))->toBeGreaterThanOrEqual($minimum);
})->with(array_map(fn (array $pair): array => [$pair[0], $pair[1], $pair[2]], BrandPalette::PAIRS));

it('computes contrast like the WCAG formula', function (): void {
    expect(BrandPalette::contrast('#000000', '#ffffff'))->toBe(21.0)
        ->and(BrandPalette::contrast('#ffffff', '#ffffff'))->toBe(1.0)
        // The brand orange cannot carry white text, which is why orange-cta exists.
        ->and(BrandPalette::contrast('#ffffff', 'orange-600'))->toBeLessThan(3.0);
});
