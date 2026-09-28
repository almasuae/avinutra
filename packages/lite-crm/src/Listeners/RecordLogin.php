<?php

declare(strict_types=1);

namespace LiteCrm\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Http\Middleware\EnforceSessionLifetime;

/**
 * Starts the session clock and records the last login of CRM users.
 */
class RecordLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof Model || ! $user instanceof CrmUser) {
            return;
        }

        if (app()->bound('session') && request()->hasSession()) {
            request()->session()->put(EnforceSessionLifetime::SESSION_KEY, Date::now()->getTimestamp());
        }

        $profile = $user->crmProfile()->first();

        if ($profile === null) {
            return;
        }

        $profile->forceFill(['last_login_at' => Date::now()])->saveQuietly();

        activity('crm')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->log('login');
    }
}
