<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LiteCrm\Database\Seeders\LiteCrmSeeder;

uses(RefreshDatabase::class);

it('seeds no users, so there is no default account or known password', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(LiteCrmSeeder::class);

    expect(User::query()->count())->toBe(0);
});

it('rejects the well-known factory password for any seeded account', function (): void {
    $this->seed(DatabaseSeeder::class);

    $weak = User::query()->get()->filter(fn (User $user): bool => Hash::check('password', $user->password));

    expect($weak)->toBeEmpty();
});

it('has no public registration for the CRM', function (): void {
    $this->get('/crm/register')->assertNotFound();
});

it('creates the first admin only through lite-crm:create-admin', function (): void {
    $this->seed(LiteCrmSeeder::class);

    $this->artisan('lite-crm:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
        ->expectsQuestion('Password (at least 12 characters)', 'a-long-secret-pass')
        ->expectsQuestion('Confirm password', 'a-long-secret-pass')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'owner@example.com')->firstOrFail();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->requiresMultiFactorAuthentication())->toBeTrue();
});
