<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

it('merges the package configuration', function (): void {
    expect(config('lite-crm.path'))->toBe('crm')
        ->and(config('lite-crm.table_prefix'))->toBe('crm_')
        ->and(config('lite-crm.base_currency'))->toBe('USD')
        ->and(config('lite-crm.modules'))->toHaveKeys([
            'organisations', 'contacts', 'enquiries', 'opportunities', 'activities', 'tasks',
            'products', 'samples', 'trials', 'quotations', 'price_log', 'documents',
            'announcements', 'decisions', 'dashboard', 'import_export',
        ]);
});

it('enables every module by default', function (): void {
    expect(array_unique(array_values(config('lite-crm.modules'))))->toBe([true]);
});

it('registers the plugin on the panel', function (): void {
    $panel = Filament::getPanel('crm');

    expect($panel->hasPlugin('lite-crm'))->toBeTrue()
        ->and($panel->getPlugin('lite-crm'))->toBeInstanceOf(LiteCrmPlugin::class);
});

it('lets a panel override module toggles', function (): void {
    $plugin = LiteCrmPlugin::make()->modules(['trials' => false]);

    expect($plugin->isModuleEnabled('trials'))->toBeFalse()
        ->and($plugin->isModuleEnabled('contacts'))->toBeTrue()
        ->and($plugin->isModuleEnabled('unknown'))->toBeFalse();
});

it('uses a translatable default navigation group', function (): void {
    expect(LiteCrmPlugin::make()->getNavigationGroup())->toBe('CRM')
        ->and(LiteCrmPlugin::make()->navigationGroup('Sales')->getNavigationGroup())->toBe('Sales');
});

it('serves the panel login page', function (): void {
    $this->get('/crm/login')->assertOk();
});

it('redirects guests to the login page', function (): void {
    $this->get('/crm')->assertRedirect('/crm/login');
});

it('marks every panel response as noindex', function (): void {
    $this->get('/crm/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/crm')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
