<?php

declare(strict_types=1);

namespace LiteCrm;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LiteCrm\Http\Middleware\NoIndex;

/**
 * Registers the CRM in a host's Filament panel.
 */
class LiteCrmPlugin implements Plugin
{
    /** @var array<string, bool>|null */
    protected ?array $modules = null;

    protected ?string $navigationGroup = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'lite-crm';
    }

    /**
     * Override the module toggles from config('lite-crm.modules') for this panel.
     *
     * @param  array<string, bool>  $modules
     */
    public function modules(array $modules): static
    {
        $this->modules = $modules;

        return $this;
    }

    /**
     * @return array<string, bool>
     */
    public function getModules(): array
    {
        /** @var array<string, bool> $configured */
        $configured = config('lite-crm.modules', []);

        return array_merge($configured, $this->modules ?? []);
    }

    public function isModuleEnabled(string $module): bool
    {
        return (bool) ($this->getModules()[$module] ?? false);
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup ?? __('lite-crm::lite-crm.navigation.group');
    }

    public function register(Panel $panel): void
    {
        $panel->middleware([NoIndex::class], isPersistent: true);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
