<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response Laravel sends (v5 §F5). Static files that
 * nginx serves directly (/build, /brand, favicon) do not pass through here; see
 * the README for the headers to add in the HestiaCP nginx template.
 *
 * Content-Security-Policy: everything is self-hosted, so only 'self' is allowed.
 * - Public site: scripts need a per-request nonce (Vite and Livewire add it);
 *   'unsafe-eval' is required by Alpine (bundled with Livewire).
 * - CRM (Filament): Filament renders a few inline scripts without a nonce, so the
 *   CRM allows 'unsafe-inline' scripts, still from this site only.
 * - Styles allow 'unsafe-inline' (Livewire, Alpine and Filament set style attributes).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');

        // One HSTS header only: Laravel sends it unless SECURITY_HSTS=false (then the web server must).
        if (config('security.hsts', true) && $request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (config('security.csp', true) && ! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', self::policy($this->isCrm($request) ? null : $nonce, $request->isSecure()));
        }

        return $response;
    }

    public static function policy(?string $nonce, bool $secure = false): string
    {
        $scripts = $nonce !== null
            ? "'self' 'nonce-{$nonce}' 'unsafe-eval'"
            : "'self' 'unsafe-inline' 'unsafe-eval'";

        return implode('; ', array_filter([
            "default-src 'self'",
            "script-src {$scripts}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'",
            "manifest-src 'self'",
            "worker-src 'self' blob:",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            $secure ? 'upgrade-insecure-requests' : null,
        ]));
    }

    protected function isCrm(Request $request): bool
    {
        $path = trim((string) config('lite-crm.path', 'crm'), '/');

        return $request->is($path, $path.'/*');
    }
}
