<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ViewField;
use LiteCrm\Filament\Resources\Roles\Pages\ManageRoles;
use LiteCrm\Filament\Resources\Roles\RoleResource;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Permissions;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    $admin = $this->crmUser(['admin']);
    $this->withMfa($admin);
    $this->actingAs($admin->refresh());
});

it('shows readable permission labels grouped by area', function (): void {
    expect(Permissions::label('documents.view_confidential'))->toBe('Documents: view confidential')
        ->and(Permissions::label('contacts.update'))->toBe('Contacts: edit')
        ->and(Permissions::label('unknown_area.some_thing'))->toBe('Unknown Area: some thing');

    $groups = RoleResource::permissionGroups();

    expect($groups)->toHaveKeys(['contacts', 'documents', 'users'])
        ->and($groups['documents']['label'])->toBe('Documents')
        ->and($groups['documents']['permissions'])->toHaveKey('documents.view_confidential', 'Documents: view confidential')
        ->and(array_sum(array_map(fn (array $g): int => count($g['permissions']), $groups)))->toBe(count(Permissions::all()));
});

it('creates a role with the chosen permissions, stored under their keys', function (): void {
    Livewire::test(ManageRoles::class)
        ->callAction('create', data: [
            'name' => 'auditor',
            'permissions' => ['contacts.view', 'documents.view_confidential', 'not.a_permission'],
        ])
        ->assertHasNoActionErrors();

    $role = LiteCrm::roleModel()::findByName('auditor', 'web');

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['contacts.view', 'documents.view_confidential']);
});

it('loads and saves the permissions of an existing role', function (): void {
    $role = LiteCrm::roleModel()::findByName('viewer', 'web');
    $before = $role->permissions->pluck('name')->all();

    Livewire::test(ManageRoles::class)
        ->mountAction(TestAction::make('edit')->table($role))
        ->assertSchemaStateSet(fn (array $state): bool => collect($state['permissions'])->sort()->values()->all() === collect($before)->sort()->values()->all())
        ->setActionData(['permissions' => ['contacts.view', 'tasks.create']])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($role->refresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['contacts.view', 'tasks.create']);
});

it('renders the grouped picker in the role form', function (): void {
    $this->get('/crm/crm-roles')->assertOk();

    // Action modals render outside the page HTML, so render the field itself.
    Livewire::test(ManageRoles::class)
        ->mountAction('create')
        ->assertSchemaComponentExists('permissions', checkComponentUsing: function (ViewField $field): bool {
            $html = $field->toHtml();

            return str_contains($html, 'data-permission-picker')
                && str_contains($html, 'data-permission-group="documents"')
                && str_contains($html, 'title="Documents: view confidential (documents.view_confidential)"');
        });
});
