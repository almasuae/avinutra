<?php

declare(strict_types=1);

namespace LiteCrm\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use LiteCrm\Contracts\CrmUser;

/**
 * Sends users who must use MFA (Admins, or everyone when an Admin requires it)
 * to the MFA set-up page until they have enabled it. Other users may enable it
 * from their profile.
 */
class RequireMultiFactorAuthentication extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof CrmUser || ! $user->requiresMultiFactorAuthentication()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
