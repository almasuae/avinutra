<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Settings\SiteSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            // Design Brief §2.4: compact logo (white-panel version in dark mode), mark as favicon.
            ->brandLogo(fn (): string => self::brandAsset('logo-compact@2x.png'))
            ->darkModeBrandLogo(fn (): string => self::brandAsset('logo-compact-boxed@2x.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): string => self::brandAsset('favicon-32.png'))
            ->colors([
                // Base at shade 500 so buttons (shade 600) are dark green with white text (AA).
                'primary' => Color::generateV3Palette('#025E3D'), // green-700
            ])
            ->plugin(
                LiteCrmPlugin::make()
                    ->modules($modules)
                    ->navigationGroup('CRM'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // The dashboard comes from LiteCrmPlugin (CrmDashboard).
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
     * A file in public/brand, with a version so browsers fetch it again after `npm run brand:build`.
     */
    protected static function brandAsset(string $file): string
    {
        return asset('brand/'.$file).'?v='.config('brand.version');
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
