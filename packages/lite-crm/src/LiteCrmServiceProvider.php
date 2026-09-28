<?php

declare(strict_types=1);

namespace LiteCrm;

use Illuminate\Support\ServiceProvider;

class LiteCrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/lite-crm.php'), 'lite-crm');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'lite-crm');
        $this->loadViewsFrom($this->packagePath('resources/views'), 'lite-crm');
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packagePath('config/lite-crm.php') => config_path('lite-crm.php'),
            ], 'lite-crm-config');

            $this->publishes([
                $this->packagePath('resources/lang') => $this->app->langPath('vendor/lite-crm'),
            ], 'lite-crm-lang');
        }
    }

    public static function packagePath(string $path = ''): string
    {
        return dirname(__DIR__).($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
    }
}
