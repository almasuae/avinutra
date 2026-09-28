<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Filament\Pages\CrmSettingsPage;
use LiteCrm\Filament\Resources\AuditLog\AuditLogResource;
use LiteCrm\Filament\Resources\CustomFields\CustomFieldResource;
use LiteCrm\Filament\Resources\CustomFields\Pages\ManageCustomFields;
use LiteCrm\Filament\Resources\Lookups\LookupResource;
use LiteCrm\Filament\Resources\Lookups\Pages\ManageLookups;
use LiteCrm\Filament\Resources\Roles\RoleResource;
use LiteCrm\Filament\Resources\Users\Pages\ManageUsers;
use LiteCrm\Filament\Resources\Users\UserResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;
use LiteCrm\Notifications\UserInvitation;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

function signInAs(TestCase $test, array $roles): User
{
    $user = $test->crmUser($roles);
    $test->withMfa($user);
    $test->actingAs($user->refresh());

    return $user;
}

it('shows each settings screen only to roles allowed to use it', function (string $role, array $allowed): void {
    signInAs($this, [$role]);

    $screens = [
        'users' => UserResource::class,
        'roles' => RoleResource::class,
        'lookups' => LookupResource::class,
        'custom_fields' => CustomFieldResource::class,
        'audit_log' => AuditLogResource::class,
        'settings' => CrmSettingsPage::class,
    ];

    foreach ($screens as $name => $class) {
        expect($class::canAccess())->toBe(in_array($name, $allowed, true), "{$role} → {$name}");
    }
})->with([
    'admin' => ['admin', ['users', 'roles', 'lookups', 'custom_fields', 'audit_log', 'settings']],
    'manager' => ['manager', ['users', 'lookups', 'audit_log']],
    'commercial' => ['commercial', ['lookups']],
    'partner' => ['partner', ['lookups']],
    'viewer' => ['viewer', ['lookups']],
]);

it('lets admins invite users from the users screen', function (): void {
    Notification::fake();
    signInAs($this, ['admin']);

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'Field Colleague',
            'email' => 'field@example.com',
            'roles' => ['partner'],
            'profile' => ['time_zone' => 'Asia/Karachi', 'city' => 'Karachi'],
        ])
        ->assertHasNoActionErrors();

    $user = User::query()->where('email', 'field@example.com')->firstOrFail();

    expect($user->hasRole('partner'))->toBeTrue()
        ->and($user->crmProfile->time_zone)->toBe('Asia/Karachi');

    Notification::assertSentTo($user, UserInvitation::class);
});

it('audit-logs role changes and never lets admins lock themselves out', function (): void {
    $admin = signInAs($this, ['admin']);
    $colleague = $this->crmUser(['viewer']);

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($colleague), data: [
            'name' => $colleague->name, 'email' => $colleague->email, 'roles' => ['manager'],
            'profile' => ['time_zone' => 'UTC', 'is_active' => true],
        ])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('edit')->table($admin), data: [
            'name' => $admin->name, 'email' => $admin->email, 'roles' => ['viewer'],
            'profile' => ['time_zone' => 'UTC', 'is_active' => false],
        ])
        ->assertHasNoActionErrors();

    expect($colleague->refresh()->hasRole('manager'))->toBeTrue()
        ->and(Activity::query()->where('event', 'roles_changed')->where('subject_id', $colleague->id)->exists())->toBeTrue()
        ->and($admin->refresh()->hasRole('admin'))->toBeTrue()
        ->and($admin->crmProfile->is_active)->toBeTrue();
});

it('lets admins manage lists, and keeps keys unique within a list', function (): void {
    signInAs($this, ['admin']);

    Livewire::test(ManageLookups::class)
        ->callAction('create', data: ['type' => 'territory', 'key' => 'north', 'label' => 'North', 'sort' => 10, 'is_active' => true])
        ->assertHasNoActionErrors()
        ->callAction('create', data: ['type' => 'territory', 'key' => 'north', 'label' => 'Duplicate', 'sort' => 20, 'is_active' => true])
        ->assertHasActionErrors(['key' => 'unique']);

    expect(Lookup::options('territory', 'key'))->toBe(['north' => 'North']);
});

it('lets non-admins view lists but not change or delete them', function (): void {
    signInAs($this, ['manager']);
    $lookup = Lookup::query()->where('type', 'organisation_type')->firstOrFail();

    Livewire::test(ManageLookups::class)
        ->assertOk()
        ->assertActionHidden('create')
        ->assertActionHidden(TestAction::make('delete')->table($lookup));
});

it('soft-deletes lists entries so they can be restored', function (): void {
    signInAs($this, ['admin']);
    $lookup = Lookup::query()->where('type', 'lost_reason')->where('key', 'timing')->firstOrFail();

    Livewire::test(ManageLookups::class)->callAction(TestAction::make('delete')->table($lookup));

    expect(Lookup::query()->find($lookup->id))->toBeNull()
        ->and(Lookup::withTrashed()->find($lookup->id))->not->toBeNull();
});

it('lets admins define custom fields with safe keys', function (): void {
    signInAs($this, ['admin']);

    Livewire::test(ManageCustomFields::class)
        ->callAction('create', data: [
            'entity' => 'organisation', 'type' => 'select', 'label' => 'Tier', 'key' => 'tier',
            'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']],
            'visible_for_types' => ['customer'], 'required' => false, 'show_in_table' => true, 'filterable' => true, 'is_active' => true, 'sort' => 0,
        ])
        ->assertHasNoActionErrors()
        ->callAction('create', data: [
            'entity' => 'organisation', 'type' => 'text', 'label' => 'Bad', 'key' => 'Bad Key!', 'is_active' => true, 'sort' => 0,
        ])
        ->assertHasActionErrors(['key']);

    $field = CustomField::query()->where('key', 'tier')->firstOrFail();

    expect($field->optionMap())->toBe(['a' => 'A', 'b' => 'B'])
        ->and($field->visible_for_types)->toBe(['customer']);
});

it('lets admins require MFA for everyone', function (): void {
    signInAs($this, ['admin']);

    Livewire::test(CrmSettingsPage::class)
        ->fillForm(['mfa_required_for_all' => true, 'role_labels' => ['specialist' => 'Technical']])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(CrmSettings::class);
    $settings->flush();

    expect($settings->isMfaRequiredForAll())->toBeTrue()
        ->and($settings->roleLabel('specialist'))->toBe('Technical');
});

it('protects the default roles from deletion', function (): void {
    signInAs($this, ['admin']);

    $role = LiteCrm::roleModel()::findByName('viewer');

    expect(RoleResource::canDelete($role))->toBeFalse();
});

it('never allows hard deletion', function (): void {
    signInAs($this, ['admin']);

    expect(LookupResource::canForceDelete(Lookup::query()->firstOrFail()))->toBeFalse();
});
