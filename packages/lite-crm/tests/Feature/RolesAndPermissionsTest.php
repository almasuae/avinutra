<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use LiteCrm\LiteCrm;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\Permissions;
use LiteCrm\Tests\TestCase;
use Spatie\Permission\PermissionRegistrar;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('gives admins every permission and every gate', function (): void {
    $admin = $this->crmUser(['admin']);

    expect(Permissions::allows($admin, 'users.manage'))->toBeTrue()
        ->and(Permissions::allows($admin, 'anything.at_all'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('some-host-ability'))->toBeTrue();
});

it('keeps managers out of user and settings management', function (): void {
    $manager = $this->crmUser(['manager']);

    expect(Permissions::allows($manager, 'contacts.view_all'))->toBeTrue()
        ->and(Permissions::allows($manager, 'website.manage'))->toBeTrue()
        ->and(Permissions::allows($manager, 'users.manage'))->toBeFalse()
        ->and(Permissions::allows($manager, 'settings.manage'))->toBeFalse()
        ->and(Permissions::allows($manager, 'contacts.delete'))->toBeFalse();
});

it('limits partners to their own records, without exports or the price log', function (): void {
    $partner = $this->crmUser(['partner']);

    expect(Permissions::allows($partner, 'contacts.view'))->toBeTrue()
        ->and(Permissions::allows($partner, 'contacts.view_all'))->toBeFalse()
        ->and(Permissions::allows($partner, 'contacts.export'))->toBeFalse()
        ->and(Permissions::allows($partner, 'price_log.view'))->toBeFalse()
        ->and(Permissions::allows($partner, 'trials.view'))->toBeFalse();
});

it('gives specialists trials and technical approval on top of commercial access', function (): void {
    $specialist = $this->crmUser(['specialist']);
    $commercial = $this->crmUser(['commercial']);

    expect(Permissions::allows($specialist, 'trials.create'))->toBeTrue()
        ->and(Permissions::allows($specialist, 'technical_content.approve'))->toBeTrue()
        ->and(Permissions::allows($specialist, 'price_log.view'))->toBeTrue()
        ->and(Permissions::allows($commercial, 'trials.view'))->toBeFalse()
        ->and(Permissions::allows($commercial, 'decisions.update'))->toBeFalse();
});

it('makes viewers read-only without exports', function (): void {
    $viewer = $this->crmUser(['viewer']);

    expect(Permissions::allows($viewer, 'organisations.view_all'))->toBeTrue()
        ->and(Permissions::allows($viewer, 'organisations.create'))->toBeFalse()
        ->and(Permissions::allows($viewer, 'organisations.export'))->toBeFalse();
});

it('reserves deletion for admins', function (): void {
    foreach (array_diff(Permissions::ROLES, ['admin']) as $role) {
        foreach (Permissions::defaultsFor($role) as $permission) {
            expect($permission)->not->toEndWith('.delete');
        }
    }
});

it('keeps permissions an admin removed when the seeder runs again', function (): void {
    $role = LiteCrm::roleModel()::findByName('commercial');
    $role->revokePermissionTo('price_log.view');

    $this->seedCrm();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($role->fresh()->hasPermissionTo('price_log.view'))->toBeFalse();
});

it('grants new permissions to the roles that should have them', function (): void {
    app(PermissionRegistrar::class)->getPermissionClass()::findByName('dashboard.view')->delete();

    $this->seedCrm();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(LiteCrm::roleModel()::findByName('viewer')->hasPermissionTo('dashboard.view'))->toBeTrue();
});

it('shows role labels that admins can rename', function (): void {
    $settings = app(CrmSettings::class);

    expect($settings->roleLabel('specialist'))->toBe('Specialist');

    $settings->set(CrmSettings::ROLE_LABELS, ['specialist' => 'Technical']);

    expect($settings->roleLabel('specialist'))->toBe('Technical')
        ->and($settings->roleLabel('viewer'))->toBe('Viewer');
});
