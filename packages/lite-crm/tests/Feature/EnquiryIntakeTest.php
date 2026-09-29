<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Events\EnquiryCaptured;
use LiteCrm\LiteCrm;
use LiteCrm\Livewire\EnquiryForm;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Lookup;
use LiteCrm\Notifications\EnquiryAcknowledgement;
use LiteCrm\Notifications\NewEnquiryNotification;
use LiteCrm\Tests\TestCase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Notification::fake();
    Storage::fake('local');
});

/**
 * The website form, filled in by a person who takes a few seconds.
 */
function enquiryForm(array $props = []): Testable
{
    $component = Livewire::test(EnquiryForm::class, $props + ['type' => 'general'])
        ->set('data.name', 'Sara Khan')
        ->set('data.email', 'Sara@Example.com')
        ->set('data.company', 'Example Trading')
        ->set('data.message', 'Please send details.')
        ->set('consent', true);

    test()->travel(5)->seconds();

    return $component;
}

it('captures an enquiry from host code and tells the team', function (): void {
    Event::fake([EnquiryCaptured::class]);

    $enquiry = LiteCrm::captureEnquiry(
        ['name' => 'Sara Khan', 'email' => 'sara@example.com', 'message' => 'Hello', 'product' => 'Widget'],
        'quotation',
        'https://example.com/products/widget',
    );

    expect($enquiry->status)->toBe(EnquiryStatus::New)
        ->and($enquiry->type->key)->toBe('quotation')
        ->and($enquiry->payload)->toBe(['product' => 'Widget'])
        ->and($enquiry->source_url)->toBe('https://example.com/products/widget')
        ->and($enquiry->channel)->toBe('manual');

    Event::assertDispatched(EnquiryCaptured::class);
});

it('e-mails admins, managers and the type\'s mailbox, but not other roles', function (): void {
    $admin = $this->crmUser(['admin']);
    $manager = $this->crmUser(['manager']);
    $commercial = $this->crmUser(['commercial']);
    Lookup::query()->where('type', 'enquiry_type')->where('key', 'general')->update(['meta' => ['mailbox' => 'info@example.com']]);

    LiteCrm::captureEnquiry(['name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hi'], 'general');

    Notification::assertSentTo([$admin, $manager], NewEnquiryNotification::class);
    Notification::assertNotSentTo($commercial, NewEnquiryNotification::class);
    Notification::assertSentOnDemand(NewEnquiryNotification::class, fn ($n, $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'info@example.com');
    // Enquiries logged by the team are not acknowledged to the sender.
    Notification::assertNotSentTo(new AnonymousNotifiable, EnquiryAcknowledgement::class);
});

it('rejects unknown types and invalid addresses', function (array $data, string $type): void {
    expect(fn () => LiteCrm::captureEnquiry($data, $type))->toThrow(ValidationException::class);
})->with([
    'unknown type' => [['name' => 'Sara'], 'no-such-type'],
    'bad e-mail' => [['email' => 'not-an-address'], 'general'],
]);

it('stores a website enquiry and acknowledges it to the sender', function (): void {
    $this->crmUser(['admin']);

    enquiryForm(['fields' => ['name' => ['required' => true], 'email' => ['required' => true], 'company', 'message', 'topic' => ['label' => 'Topic', 'type' => 'select', 'options' => ['a' => 'A', 'b' => 'B']]]])
        ->set('data.topic', 'b')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Thank you');

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->status)->toBe(EnquiryStatus::New)
        ->and($enquiry->email)->toBe('sara@example.com')
        ->and($enquiry->payload)->toBe(['topic' => 'b'])
        ->and($enquiry->channel)->toBe('form')
        ->and($enquiry->consent_given)->toBeTrue()
        ->and($enquiry->consent_at)->not->toBeNull()
        ->and($enquiry->ip_hash)->not->toBeNull()->not->toBe('127.0.0.1');

    Notification::assertSentOnDemand(EnquiryAcknowledgement::class, fn ($n, $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'sara@example.com');
    Notification::assertCount(2);
});

it('requires consent and the required fields', function (): void {
    Livewire::test(EnquiryForm::class, ['type' => 'general'])
        ->set('data.message', 'Hello')
        ->call('submit')
        ->assertHasErrors(['data.name', 'data.email', 'consent']);

    expect(Enquiry::query()->count())->toBe(0);
});

it('keeps honeypot spam for review, without e-mails, and thanks the bot as usual', function (): void {
    $this->crmUser(['admin']);

    enquiryForm()->set('website', 'http://spam.example')->call('submit')->assertSet('submitted', true);

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->status)->toBe(EnquiryStatus::Spam)
        ->and($enquiry->spam_reason)->toBe('honeypot');

    Notification::assertNothingSent();
});

it('marks forms sent faster than a person could fill them as spam', function (): void {
    Livewire::test(EnquiryForm::class, ['type' => 'general'])
        ->set('data.name', 'Bot')
        ->set('data.email', 'bot@example.com')
        ->set('data.message', 'x')
        ->set('consent', true)
        ->call('submit');

    expect(Enquiry::query()->sole()->spam_reason)->toBe('too_fast');
});

it('limits the number of enquiries per IP address', function (): void {
    config(['lite-crm.enquiries.rate_limit.attempts' => 2]);

    enquiryForm()->call('submit');
    enquiryForm()->call('submit');
    enquiryForm()->call('submit')->assertHasErrors('form')->assertSet('submitted', false);

    expect(Enquiry::query()->count())->toBe(2);
});

it('stores uploads as private documents on the enquiry', function (): void {
    enquiryForm(['uploads' => 2])
        ->set('attachments', [UploadedFile::fake()->create('specification.pdf', 300, 'application/pdf')])
        ->call('submit')
        ->assertHasNoErrors();

    $document = Enquiry::query()->sole()->documents()->sole();

    expect($document->file_name)->toBe('specification.pdf')
        ->and($document->disk)->toBe('local')
        ->and($document->file_path)->toStartWith('crm/documents/');

    Storage::disk('local')->assertExists($document->file_path);
});

it('refuses uploads of the wrong type, too large, or too many', function (array $files): void {
    enquiryForm(['uploads' => 2])
        ->set('attachments', $files)
        ->call('submit')
        ->assertHasErrors();

    expect(Enquiry::query()->count())->toBe(0);
})->with([
    'wrong type' => [[UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')]],
    'too large' => [[UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf')]],
    'too many' => [[
        UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('c.pdf', 10, 'application/pdf'),
    ]],
]);

it('discards files sent with spam', function (): void {
    enquiryForm(['uploads' => 1])
        ->set('website', 'bot')
        ->set('attachments', [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->call('submit');

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->documents()->count())->toBe(0)
        ->and($enquiry->payload)->toBe(['_discarded_attachments' => 1]);
});

it('mentions a response time only when one is configured', function (): void {
    $enquiry = LiteCrm::captureEnquiry(['email' => 'sara@example.com', 'message' => 'Hi'], 'general');

    $without = (new EnquiryAcknowledgement($enquiry))->toMail(new AnonymousNotifiable)->render();
    config(['lite-crm.enquiries.response_time' => 'within two working days']);
    $with = (new EnquiryAcknowledgement($enquiry))->toMail(new AnonymousNotifiable)->render();

    expect((string) $without)->not->toContain('aim to reply')
        ->and((string) $with)->toContain('We aim to reply within two working days.');
});

it('lets the host supply the response time', function (): void {
    $enquiry = LiteCrm::captureEnquiry(['email' => 'sara@example.com', 'message' => 'Hi'], 'general');

    LiteCrm::resolveEnquiryResponseTimeUsing(fn (): string => 'within one working day');
    $mail = (string) (new EnquiryAcknowledgement($enquiry))->toMail(new AnonymousNotifiable)->render();
    LiteCrm::resolveEnquiryResponseTimeUsing(fn (): string => '  ');
    $blank = (string) (new EnquiryAcknowledgement($enquiry))->toMail(new AnonymousNotifiable)->render();
    LiteCrm::resolveEnquiryResponseTimeUsing(null);

    expect($mail)->toContain('We aim to reply within one working day.')
        ->and($blank)->not->toContain('aim to reply');
});
