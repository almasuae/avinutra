<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Links that exist only when their page exists: a page that is not built yet is
 * never linked (Content Blueprint v3 §6).
 */
class SiteLinks
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function url(string $routeName, array $parameters = []): ?string
    {
        return Route::has($routeName) ? route($routeName, $parameters) : null;
    }

    /**
     * WhatsApp click-to-chat link for a number from Site settings, or null.
     */
    public static function whatsapp(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return $digits !== '' && $digits !== null ? 'https://wa.me/'.$digits : null;
    }
}
