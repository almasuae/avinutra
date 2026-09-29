<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Database\Seeders\LiteCrmSeeder;
use LiteCrm\Models\Organisation;

uses(RefreshDatabase::class);

function crmMember(array $roles): User
{
    $user = User::factory()->create();
    $user->crmProfile()->create(['time_zone' => 'Asia/Karachi']);
    $user->syncRoles($roles);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    return $user->refresh();
}

beforeEach(fn () => $this->seed(LiteCrmSeeder::class));

it('opens the CRM record screens in the AviNutra panel', function (string $path): void {
    $this->actingAs(crmMember(['admin']))->get('/crm/'.$path)->assertOk();
})->with(['enquiries', 'organisations', 'contacts', 'activities', 'tasks', 'documents', 'opportunities', 'opportunity-board', 'products', 'samples', 'trials', 'quotations', 'price-log', 'announcements', 'decisions']);

it('shows an organisation with its related records', function (): void {
    $admin = crmMember(['admin']);
    $this->actingAs($admin);
    $organisation = Organisation::query()->create(['name' => 'Example Trading']);

    $this->get('/crm/organisations/'.$organisation->id)
        ->assertOk()
        ->assertSee('Example Trading')
        ->assertSee('Log activity');
});

it('keeps viewers read-only', function (): void {
    $this->actingAs(crmMember(['viewer']))->get('/crm/organisations/create')->assertForbidden();
});
