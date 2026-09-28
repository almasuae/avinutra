<?php

declare(strict_types=1);

namespace LiteCrm;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use LiteCrm\Console\CreateAdminCommand;
use LiteCrm\Console\InstallCommand;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldValidator;
use LiteCrm\Filament\Pages\AcceptInvitation;
use LiteCrm\Listeners\RecordLogin;
use LiteCrm\Support\CrmSettings;
use Livewire\Livewire;

class LiteCrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/lite-crm.php'), 'lite-crm');

        // Per request, so long-running workers never serve stale definitions or settings.
        $this->app->scoped(CustomFieldRegistry::class);
        $this->app->scoped(CustomFieldValidator::class);
        $this->app->scoped(CrmSettings::class);

        // After every provider has registered (and merged its config), before any boots.
        $this->app->booting(function (): void {
            $this->prefixThirdPartyTables();
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'lite-crm');
        $this->loadViewsFrom($this->packagePath('resources/views'), 'lite-crm');
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        // Admins hold every permission (v5 §D1).
        Gate::before(fn (mixed $user): ?bool => LiteCrm::isSuperAdmin($user) ? true : null);

        Event::listen(Login::class, RecordLogin::class);

        Livewire::component('lite-crm.accept-invitation', AcceptInvitation::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CreateAdminCommand::class,
            ]);

            $this->publishes([
                $this->packagePath('config/lite-crm.php') => config_path('lite-crm.php'),
            ], 'lite-crm-config');

            $this->publishes([
                $this->packagePath('resources/lang') => $this->app->langPath('vendor/lite-crm'),
            ], 'lite-crm-lang');
        }
    }

    /**
     * Store roles, permissions and the audit log in crm_-prefixed tables, unless
     * the host shares its own (lite-crm.prefix_third_party_tables = false).
     */
    protected function prefixThirdPartyTables(): void
    {
        if (! config('lite-crm.prefix_third_party_tables', true)) {
            return;
        }

        config([
            'permission.table_names' => [
                'roles' => LiteCrm::table('roles'),
                'permissions' => LiteCrm::table('permissions'),
                'model_has_permissions' => LiteCrm::table('model_has_permissions'),
                'model_has_roles' => LiteCrm::table('model_has_roles'),
                'role_has_permissions' => LiteCrm::table('role_has_permissions'),
            ],
            'activitylog.table_name' => LiteCrm::table('activity_log'),
        ]);
    }

    public static function packagePath(string $path = ''): string
    {
        return dirname(__DIR__).($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
    }
}
