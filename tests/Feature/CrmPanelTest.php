<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

it('has no public registration', function (): void {
    $this->get('/crm/register')->assertNotFound();
});

it('lets a team member into the CRM', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/crm')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
