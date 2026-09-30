<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Header / hero separation (owner's design request, 30 Sep 2026): the one setting that
 * picks the look of the home hero and the inner-page hero band. The styles themselves
 * are CSS variables in resources/css/app.css ([data-hero=…]).
 *
 * Change the look in config/site.php ('hero_style') or with SITE_HERO_STYLE in .env.
 * On a local installation, ?hero=A (B, C, D, current) previews a style on any page.
 */
class HeroStyle
{
    public const OPTIONS = [
        'current' => 'White to warm-grey gradient; header shadow when scrolled (the look before the request)',
        'A' => 'Pale green tint; header line; header shadow when scrolled',
        'B' => 'Warm grey (surface); header line; header shadow when scrolled',
        'C' => 'Pale green to white gradient with faint leaf lines; header line; header shadow when scrolled',
        'D' => 'White hero; header line and shadow always',
    ];

    public const DEFAULT = 'current';

    public static function current(): string
    {
        if (app()->isLocal() && app()->bound('request')) {
            $preview = request()->query('hero');

            if (is_string($preview) && array_key_exists($preview, self::OPTIONS)) {
                return $preview;
            }
        }

        $style = config('site.hero_style');

        return is_string($style) && array_key_exists($style, self::OPTIONS) ? $style : self::DEFAULT;
    }
}
