<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Database\Seeders\LiteCrmSeeder;
use LiteCrm\Livewire\EnquiryForm;
use LiteCrm\Models\Enquiry;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(LiteCrmSeeder::class));

it('does not expose the local test form outside local development', function (): void {
    expect(app()->environment('local'))->toBeFalse();

    $this->get('/dev/enquiry-form')->assertNotFound();
});

it('keeps the intake API off in this installation by default', function (): void {
    $this->postJson('/crm-api/enquiries', ['type' => 'general'])->assertNotFound();
});

it('sends a website enquiry into the CRM inbox', function (): void {
    Notification::fake();

    Livewire::test(EnquiryForm::class, ['type' => 'general'])
        ->set('data.name', 'Test Sender')
        ->set('data.email', 'sender@example.com')
        ->set('data.message', 'A test enquiry.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasNoErrors();

    expect(Enquiry::query()->sole()->email)->toBe('sender@example.com');
});
