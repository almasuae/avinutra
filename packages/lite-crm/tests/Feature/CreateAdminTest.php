<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use LiteCrm\LiteCrm;
use LiteCrm\Notifications\UserInvitation;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('creates an admin with a password entered interactively', function (): void {
    $this->artisan('lite-crm:create-admin', ['email' => 'Owner@Example.com', '--name' => 'Owner'])
        ->expectsQuestion('Password (at least 12 characters)', 'a-long-secret-pass')
        ->expectsQuestion('Confirm password', 'a-long-secret-pass')
        ->assertSuccessful();

    $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

    expect($user->hasRole('admin'))->toBeTrue()
        ->and(Hash::check('a-long-secret-pass', $user->password))->toBeTrue()
        ->and($user->canAccessCrm())->toBeTrue()
        ->and($user->requiresMultiFactorAuthentication())->toBeTrue();
});

it('rejects passwords shorter than 12 characters', function (): void {
    $command = $this->artisan('lite-crm:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner']);

    foreach (range(1, 3) as $attempt) {
        $command->expectsQuestion('Password (at least 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short');
    }

    $command->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('will not take over an existing account', function (): void {
    $this->crmUser(['viewer'], email: 'taken@example.com');

    $this->artisan('lite-crm:create-admin', ['email' => 'taken@example.com', '--name' => 'X'])->assertFailed();

    expect(User::query()->where('email', 'taken@example.com')->firstOrFail()->hasRole('admin'))->toBeFalse();
});

it('needs a terminal to set a password', function (): void {
    $this->artisan('lite-crm:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner', '--no-interaction' => true])
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('can e-mail an invitation instead', function (): void {
    Notification::fake();

    $this->artisan('lite-crm:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner', '--invite' => true, '--no-interaction' => true])
        ->assertSuccessful();

    $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

    Notification::assertSentTo($user, UserInvitation::class);

    // Cannot sign in until the invitation is accepted.
    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->canAccessCrm())->toBeFalse();
});

it('fails before installation', function (): void {
    LiteCrm::roleModel()::query()->delete();

    $this->artisan('lite-crm:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner', '--invite' => true])
        ->assertFailed();
});
