<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Settings\SiteSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LiteCrm\LiteCrmPlugin;
use Throwable;

class CrmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        /** @var array<string, bool> $modules */
        $modules = config('lite-crm.modules', []);

        return $panel
            ->default()
            ->id('crm')
            ->path(config('lite-crm.path', 'crm'))
            ->login()
            ->brandName(fn (): string => $this->brandName())
            ->colors([
                'primary' => Color::hex('#1F4E5F'),
            ])
            ->plugin(
                LiteCrmPlugin::make()
                    ->modules($modules)
                    ->navigationGroup('CRM'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * The brand from Site settings; falls back to APP_NAME before the settings exist
     * (e.g. while migrations are running on a fresh install).
     */
    protected function brandName(): string
    {
        try {
            return app(SiteSettings::class)->brand;
        } catch (Throwable) {
            return (string) config('app.name');
        }
    }
}
