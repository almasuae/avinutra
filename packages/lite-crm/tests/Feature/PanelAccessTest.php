<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use LiteCrm\Http\Middleware\EnforceSessionLifetime;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('has no public registration', function (): void {
    $this->get('/crm/register')->assertNotFound();
});

it('refuses users who have no CRM profile', function (): void {
    $user = User::query()->create(['name' => 'Host user', 'email' => 'host@example.com', 'password' => 'correct-horse-battery']);

    $this->actingAs($user)->get('/crm')->assertForbidden();
});

it('refuses users without a role', function (): void {
    $this->actingAs($this->crmUser([]))->get('/crm')->assertForbidden();
});

it('refuses deactivated users', function (): void {
    $this->actingAs($this->crmUser(['commercial'], ['is_active' => false]))->get('/crm')->assertForbidden();
});

it('lets an active team member in without MFA when their role does not need it', function (): void {
    $this->actingAs($this->crmUser(['commercial']))->get('/crm')->assertOk();
});

it('sends admins to MFA set-up until they have enabled it', function (): void {
    $admin = $this->crmUser(['admin']);

    $this->actingAs($admin)->get('/crm')->assertRedirect(Filament::getPanel('crm')->getSetUpRequiredMultiFactorAuthenticationUrl());

    $this->withMfa($admin);

    $this->actingAs($admin->refresh())->get('/crm')->assertOk();
});

it('sends everyone to MFA set-up when an admin requires it for all users', function (): void {
    app(CrmSettings::class)->set(CrmSettings::MFA_REQUIRED_FOR_ALL, true);

    $this->actingAs($this->crmUser(['viewer']))->get('/crm')->assertRedirect();
});

it('ends the session eight hours after login', function (): void {
    $user = $this->crmUser(['commercial']);

    $this->actingAs($user)
        ->withSession([EnforceSessionLifetime::SESSION_KEY => now()->subMinutes(479)->getTimestamp()])
        ->get('/crm')
        ->assertOk();

    $this->actingAs($user)
        ->withSession([EnforceSessionLifetime::SESSION_KEY => now()->subMinutes(481)->getTimestamp()])
        ->get('/crm')
        ->assertRedirect('/crm/login');

    $this->assertGuest();
});

it('records the last login and writes it to the audit log', function (): void {
    $user = $this->crmUser(['commercial']);

    event(new Login('web', $user, false));

    expect($user->crmProfile()->first()->last_login_at)->not->toBeNull()
        ->and(Activity::query()->where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();
});

it('shows dates in the user\'s own time zone', function (): void {
    $user = $this->crmUser(['commercial'], ['time_zone' => 'Asia/Singapore']);

    $this->actingAs($user)->get('/crm')->assertOk();

    expect(FilamentTimezone::get())->toBe('Asia/Singapore');
});

it('throttles login attempts', function (): void {
    $this->crmUser(['commercial'], email: 'member@example.com');

    $component = Livewire::test(\Filament\Auth\Pages\Login::class);

    foreach (range(1, 5) as $attempt) {
        $component->fillForm(['email' => 'member@example.com', 'password' => 'wrong-password'])->call('authenticate');
    }

    $component->fillForm(['email' => 'member@example.com', 'password' => 'correct-horse-battery'])->call('authenticate');

    $this->assertGuest();
});
