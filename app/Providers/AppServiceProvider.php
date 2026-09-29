<?php

declare(strict_types=1);

namespace App\Providers;

use App\Settings\SiteSettings;
use Illuminate\Support\ServiceProvider;
use LiteCrm\LiteCrm;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LiteCrm::resolveContractingEntityUsing(fn (): ?string => self::contractingEntity());
    }

    /**
     * The entity named on quotations: the Singapore company once incorporated,
     * otherwise the Pakistan partner, otherwise nothing (the user fills it in).
     */
    public static function contractingEntity(): ?string
    {
        try {
            $site = app(SiteSettings::class);
        } catch (Throwable) {
            return null;
        }

        if ($site->sg_incorporated && filled($site->legal_name)) {
            return $site->legal_name;
        }

        if (filled($site->pk_partner_name)) {
            return trim($site->pk_partner_name.(filled($site->pk_partner_city) ? ', '.$site->pk_partner_city : ''));
        }

        return null;
    }
}
