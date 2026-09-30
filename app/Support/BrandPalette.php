<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The brand colours (Design Brief §3; the same values as the @theme tokens in
 * resources/css/app.css) and the text/background pairs the site uses, with
 * their WCAG contrast. A unit test keeps both in sync and every pair at AA.
 */
class BrandPalette
{
    public const TOKENS = [
        'green-900' => '#013b32',
        'green-800' => '#034c33',
        'green-700' => '#025e3d',
        'green-500' => '#6ba727',
        'orange-600' => '#fc6b01',
        'orange-500' => '#fc7804',
        'orange-400' => '#fd9302',
        'orange-cta' => '#f06501',
        'orange-text' => '#b94e01',
        'ink' => '#1c2226',
        'muted' => '#5b6770',
        'surface' => '#f7f8f6',
        'line' => '#e3e7e4',
        'hero-tint' => '#f2f7ef',
        'on-dark' => '#ffffff',
        'on-dark-muted' => '#c9d6cf',
    ];

    /**
     * Text pairs in use: [text, background, minimum ratio, where].
     * 4.5 = normal text; 3.0 = large text (>= 24px, or >= 18.66px bold).
     *
     * @var list<array{0: string, 1: string, 2: float, 3: string}>
     */
    public const PAIRS = [
        ['ink', '#ffffff', 4.5, 'Body text'],
        ['ink', 'surface', 4.5, 'Body text on sections and cards'],
        ['muted', '#ffffff', 4.5, 'Secondary text'],
        ['muted', 'surface', 4.5, 'Secondary text, light footer'],
        ['green-900', '#ffffff', 4.5, 'Headings'],
        ['green-900', 'surface', 4.5, 'Headings on sections'],
        ['green-700', '#ffffff', 4.5, 'Links, active navigation'],
        ['green-700', 'surface', 4.5, 'Links on sections'],
        ['orange-text', '#ffffff', 4.5, 'Eyebrow labels'],
        ['orange-text', 'surface', 4.5, 'Eyebrow labels on sections'],
        ['#ffffff', 'orange-cta', 3.0, 'Primary button (19px bold labels)'],
        ['#ffffff', 'orange-text', 4.5, 'Primary button, hover'],
        ['#ffffff', 'green-800', 4.5, 'Header CTA, dark footer headings'],
        ['green-800', '#ffffff', 4.5, 'Secondary button'],
        ['on-dark-muted', 'green-800', 4.5, 'Dark footer text and links'],
        // Hero options A and C (pale green tint); #eef5ec = the tint under a 1.5% leaf line of option C.
        ['ink', 'hero-tint', 4.5, 'Hero text, options A/C'],
        ['muted', 'hero-tint', 4.5, 'Breadcrumb, options A/C'],
        ['green-900', 'hero-tint', 4.5, 'Hero heading, options A/C'],
        ['green-700', 'hero-tint', 4.5, 'Breadcrumb links, options A/C'],
        ['orange-text', 'hero-tint', 4.5, 'Hero eyebrow, options A/C'],
        ['green-800', 'hero-tint', 4.5, 'Secondary button, options A/C'],
        ['ink', '#eef5ec', 4.5, 'Hero text on a leaf line, option C'],
        ['muted', '#eef5ec', 4.5, 'Breadcrumb on a leaf line, option C'],
        ['orange-text', '#eef5ec', 4.5, 'Hero eyebrow on a leaf line, option C'],
        ['green-900', '#eef5ec', 4.5, 'Hero heading on a leaf line, option C'],
        // Option B uses surface (pairs above); option D keeps the hero white.
        ['green-800', 'surface', 4.5, 'Secondary button, option B'],
    ];

    public static function hex(string $token): string
    {
        return self::TOKENS[$token] ?? $token;
    }

    public static function contrast(string $foreground, string $background): float
    {
        $a = self::luminance(self::hex($foreground));
        $b = self::luminance(self::hex($background));

        return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
    }

    protected static function luminance(string $hex): float
    {
        $channels = array_map(
            function (string $pair): float {
                $value = hexdec($pair) / 255;

                return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            str_split(ltrim($hex, '#'), 2),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
