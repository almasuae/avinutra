<?php

declare(strict_types=1);

namespace LiteCrm\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends a CRM session a fixed time after login (default 8 hours), whatever the activity.
 */
class EnforceSessionLifetime
{
    public const SESSION_KEY = 'lite_crm.authenticated_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! Filament::auth()->check()) {
            return $next($request);
        }

        $session = $request->session();
        $authenticatedAt = $session->get(self::SESSION_KEY);

        if ($authenticatedAt === null) {
            $session->put(self::SESSION_KEY, Date::now()->getTimestamp());

            return $next($request);
        }

        $lifetime = (int) config('lite-crm.auth.session_lifetime_minutes', 480) * 60;

        if (Date::now()->getTimestamp() - (int) $authenticatedAt < $lifetime) {
            return $next($request);
        }

        Filament::auth()->logout();
        $session->invalidate();
        $session->regenerateToken();

        return redirect()->guest(Filament::getLoginUrl() ?? '/');
    }
}
