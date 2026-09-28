<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Database\Seeders\LiteCrmSeeder;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(LiteCrmSeeder::class));

function teamMember(array $roles = ['commercial']): User
{
    $user = User::factory()->create();
    $user->crmProfile()->create(['time_zone' => 'Asia/Karachi']);
    $user->syncRoles($roles);

    return $user->refresh();
}

it('registers the CRM panel with the Lite CRM plugin', function (): void {
    $panel = Filament::getPanel('crm');

    expect($panel->getPath())->toBe('crm')
        ->and($panel->hasPlugin('lite-crm'))->toBeTrue();
});

it('requires login for the CRM', function (): void {
    $this->get('/crm')->assertRedirect('/crm/login');
});

it('serves the CRM login page with noindex', function (): void {
    $this->get('/crm/login')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('lets a team member into the CRM', function (): void {
    $this->actingAs(teamMember())
        ->get('/crm')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('keeps out a user who was never invited to the CRM', function (): void {
    $this->actingAs(User::factory()->create())->get('/crm')->assertForbidden();
});

it('sends admins to MFA set-up first', function (): void {
    $this->actingAs(teamMember(['admin']))
        ->get('/crm')
        ->assertRedirect(Filament::getPanel('crm')->getSetUpRequiredMultiFactorAuthenticationUrl());
});

it('uses the brand from Site settings', function (): void {
    expect(Filament::getPanel('crm')->getBrandName())->toBe('AviNutra');
});
