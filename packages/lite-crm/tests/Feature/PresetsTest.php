<?php

declare(strict_types=1);

use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Pipeline;
use LiteCrm\Presets\PresetLoader;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
});

it('installs as a neutral CRM with no industry fields', function (): void {
    expect(CustomField::query()->count())->toBe(0)
        ->and(Pipeline::query()->where('key', 'sales_feed_mills')->exists())->toBeFalse()
        ->and(LiteCrm::baseCurrency())->toBe(config('lite-crm.base_currency'));
});

it('lists the presets that ship with the package', function (): void {
    expect(app(PresetLoader::class)->available())->toHaveKey('feed-additives');

    $this->artisan('lite-crm:preset', ['--list' => true])
        ->expectsOutputToContain('feed-additives')
        ->assertSuccessful();
});

it('applies the feed-additives preset on top of the neutral CRM', function (): void {
    $this->artisan('lite-crm:preset', ['name' => 'feed-additives'])->assertSuccessful();

    expect(CustomField::query()->where('entity', 'organisation')->where('key', 'methionine_use_t_year')->exists())->toBeTrue()
        ->and(CustomField::query()->where('entity', 'trial')->where('key', 'fcr')->exists())->toBeTrue()
        ->and(Pipeline::query()->where('key', 'sales_feed_mills')->where('is_active', true)->exists())->toBeTrue()
        ->and(Lookup::options('organisation_type'))->toContain('Feed mill')
        ->and(LiteCrm::currencies())->toContain('PKR')
        ->and(LiteCrm::baseCurrency())->toBe('USD')
        ->and(LiteCrm::quotationPrefix())->toBe('AVN-Q');
});

it('can apply a preset twice without duplicating anything', function (): void {
    $loader = app(PresetLoader::class);
    $loader->apply($loader->load('feed-additives'));

    $counts = [CustomField::query()->count(), Lookup::query()->count(), Pipeline::query()->count()];

    $second = $loader->apply($loader->load('feed-additives'));

    expect([CustomField::query()->count(), Lookup::query()->count(), Pipeline::query()->count()])->toBe($counts)
        ->and($second['lookups'])->toBe(0)
        ->and($second['custom_fields'])->toBe(0);
});

it('deactivates replaced list entries instead of deleting them', function (): void {
    $neutral = Lookup::query()->where('type', 'organisation_type')->pluck('key')->all();

    $loader = app(PresetLoader::class);
    $loader->apply($loader->load('feed-additives'));

    $preset = array_keys($loader->load('feed-additives')['lookups']['organisation_type']['items']);
    $retired = array_diff($neutral, $preset);

    expect(Lookup::query()->where('type', 'organisation_type')->whereIn('key', $neutral)->count())->toBe(count($neutral));

    if ($retired !== []) {
        expect(Lookup::query()->where('type', 'organisation_type')->whereIn('key', $retired)->where('is_active', true)->count())->toBe(0);
    }
});

it('refuses an unknown preset', function (): void {
    $this->artisan('lite-crm:preset', ['name' => 'does-not-exist'])->assertFailed();
});
