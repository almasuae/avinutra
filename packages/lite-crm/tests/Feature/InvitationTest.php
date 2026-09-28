<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Actions\InviteUser;
use LiteCrm\Filament\Pages\AcceptInvitation;
use LiteCrm\Notifications\UserInvitation;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Notification::fake();
});

function invite(array $overrides = []): User
{
    /** @var User $user */
    $user = app(InviteUser::class)->handle(array_merge([
        'name' => 'New Colleague',
        'email' => 'colleague@example.com',
        'roles' => ['commercial'],
        'profile' => ['city' => 'Lahore', 'time_zone' => 'Asia/Karachi'],
    ], $overrides));

    return $user;
}

function invitationUrl(User $user): string
{
    $url = null;

    Notification::assertSentTo($user, UserInvitation::class, function (UserInvitation $notification) use (&$url): bool {
        $url = $notification->url;

        return true;
    });

    return (string) $url;
}

it('creates a pending user with an unusable password and e-mails a signed link', function (): void {
    $user = invite();

    expect($user->hasRole('commercial'))->toBeTrue()
        ->and($user->crmProfile->city)->toBe('Lahore')
        ->and($user->crmProfile->isInvitationPending())->toBeTrue()
        ->and($user->canAccessCrm())->toBeFalse()
        ->and(invitationUrl($user))->toContain('/crm/invitation/'.$user->id)->toContain('signature=');
});

it('shows the invitation page for a valid link', function (): void {
    $user = invite();

    $this->get(invitationUrl($user))->assertOk()->assertSee('colleague@example.com');
});

it('rejects a tampered link', function (): void {
    $user = invite();

    $this->get(invitationUrl($user).'x')->assertForbidden();
});

it('rejects an expired link', function (): void {
    $user = invite();
    $url = invitationUrl($user);

    $this->travel(73)->hours();

    $this->get($url)->assertForbidden();
});

it('invalidates older links when the invitation is resent', function (): void {
    $user = invite();
    $firstUrl = invitationUrl($user);

    $this->travel(5)->seconds();
    app(InviteUser::class)->send($user);

    $this->get($firstUrl)->assertForbidden();
});

it('lets the user choose a password and signs them in', function (): void {
    $user = invite();
    $url = invitationUrl($user);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    $this->get($url)->assertOk();

    Livewire::withQueryParams($query)
        ->test(AcceptInvitation::class, ['user' => $user->id])
        ->fillForm(['password' => 'a-long-secret-pass', 'passwordConfirmation' => 'a-long-secret-pass'])
        ->call('accept')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $user->refresh();

    expect(Hash::check('a-long-secret-pass', $user->password))->toBeTrue()
        ->and($user->crmProfile->invitation_accepted_at)->not->toBeNull()
        ->and($user->canAccessCrm())->toBeTrue();

    $this->assertAuthenticatedAs($user);

    // The link cannot be used twice.
    $this->app['auth']->logout();
    $this->get($url)->assertForbidden();
});

it('requires a password of at least 12 characters', function (): void {
    $user = invite();
    parse_str((string) parse_url(invitationUrl($user), PHP_URL_QUERY), $query);

    Livewire::withQueryParams($query)
        ->test(AcceptInvitation::class, ['user' => $user->id])
        ->fillForm(['password' => 'too-short', 'passwordConfirmation' => 'too-short'])
        ->call('accept')
        ->assertHasFormErrors(['password']);

    expect($user->refresh()->crmProfile->isInvitationPending())->toBeTrue();
});

it('does not let a deactivated user accept', function (): void {
    $user = invite();
    $url = invitationUrl($user);
    $user->crmProfile->update(['is_active' => false]);

    $this->get($url)->assertForbidden();
});
