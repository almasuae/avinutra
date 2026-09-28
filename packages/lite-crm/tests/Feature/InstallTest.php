<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Pipeline;
use LiteCrm\Models\PipelineStage;
use LiteCrm\Support\Permissions;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Spatie\Permission\PermissionRegistrar;

uses(TestCase::class);

it('creates every package table with the crm_ prefix', function (string $table): void {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'crm_settings', 'crm_lookups', 'crm_pipelines', 'crm_pipeline_stages', 'crm_tags', 'crm_taggables',
    'crm_custom_fields', 'crm_user_profiles',
    'crm_roles', 'crm_permissions', 'crm_model_has_roles', 'crm_model_has_permissions', 'crm_role_has_permissions',
    'crm_activity_log',
]);

it('does not create unprefixed third-party tables', function (): void {
    expect(Schema::hasTable('roles'))->toBeFalse()
        ->and(Schema::hasTable('permissions'))->toBeFalse()
        ->and(Schema::hasTable('activity_log'))->toBeFalse();
});

it('never alters the host users table', function (): void {
    expect(Schema::getColumnListing('users'))->not->toContain('time_zone', 'app_authentication_secret');
});

it('installs roles, permissions and neutral lookups without creating users', function (): void {
    $this->artisan('lite-crm:install')->assertSuccessful();

    expect(LiteCrm::roleModel()::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(Permissions::ROLES)->sort()->values()->all())
        ->and(app(PermissionRegistrar::class)->getPermissionClass()::query()->count())
        ->toBe(count(Permissions::all()))
        ->and(Lookup::query()->ofType('organisation_type')->pluck('key')->all())
        ->toContain('customer', 'supplier')
        ->and(Pipeline::query()->where('key', 'sales')->firstOrFail()->stages()->count())->toBe(6)
        ->and(User::query()->count())->toBe(0);
});

it('can be run again without creating duplicates', function (): void {
    $this->artisan('lite-crm:install')->assertSuccessful();

    $counts = fn (): array => [
        LiteCrm::roleModel()::query()->count(),
        Lookup::query()->count(),
        Pipeline::query()->count(),
        PipelineStage::query()->count(),
        app(PermissionRegistrar::class)->getPermissionClass()::query()->count(),
    ];

    $before = $counts();
    $this->artisan('lite-crm:install')->assertSuccessful();

    expect($counts())->toBe($before);
});

it('keeps lookup labels that an admin has edited', function (): void {
    $this->seedCrm();

    Lookup::query()->where('type', 'organisation_type')->where('key', 'customer')->update(['label' => 'Client']);

    $this->seedCrm();

    expect(Lookup::query()->where('key', 'customer')->value('label'))->toBe('Client');
});
