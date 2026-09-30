<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The header / hero look of every public page (owner's decision, 1 Oct 2026: option A).
 * One setting — config('site.hero_style'), or SITE_HERO_STYLE in .env — rendered as
 * <html data-hero="…">; the styles are CSS variables in resources/css/app.css, applied
 * to <x-page.hero-band> (the top section of every page) and the site header.
 */
class HeroStyle
{
    public const OPTIONS = [
        'A' => 'Pale green hero band (#F2F7EF); header line; header shadow when scrolled',
        'original' => 'White to warm-grey hero; no header line; header shadow when scrolled (the look before 1 Oct 2026)',
    ];

    public const DEFAULT = 'A';

    public static function current(): string
    {
        $style = config('site.hero_style');

        return is_string($style) && array_key_exists($style, self::OPTIONS) ? $style : self::DEFAULT;
    }
}
