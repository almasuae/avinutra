<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use LiteCrm\Enquiries\ApiToken;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Models\Enquiry;
use LiteCrm\Notifications\EnquiryAcknowledgement;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Notification::fake();
});

function enquiryPayload(array $overrides = []): array
{
    return $overrides + [
        'type' => 'general',
        'name' => 'Sara Khan',
        'email' => 'sara@example.com',
        'message' => 'Please call me.',
        'consent' => true,
        'source_url' => 'https://partner-site.example/contact',
    ];
}

it('is switched off by default', function (): void {
    $token = app(ApiToken::class)->rotate();

    $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload())->assertNotFound();

    expect(Enquiry::query()->count())->toBe(0);
});

it('refuses requests without a valid token', function (?string $token): void {
    config(['lite-crm.enquiry_api.enabled' => true]);
    app(ApiToken::class)->rotate();

    $request = $token === null ? $this : $this->withToken($token);
    $request->postJson('/crm-api/enquiries', enquiryPayload())->assertUnauthorized();

    expect(Enquiry::query()->count())->toBe(0);
})->with(['no token' => [null], 'wrong token' => ['lcrm_wrong']]);

it('refuses everything until a token has been generated', function (): void {
    config(['lite-crm.enquiry_api.enabled' => true]);

    $this->withToken('')->postJson('/crm-api/enquiries', enquiryPayload())->assertUnauthorized();
});

it('accepts an enquiry with a valid token and acknowledges it', function (): void {
    config(['lite-crm.enquiry_api.enabled' => true]);
    $token = app(ApiToken::class)->rotate();

    $response = $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload(['sector' => 'Retail']));

    $response->assertStatus(202)->assertJson(['status' => 'received']);

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->channel)->toBe('api')
        ->and($enquiry->payload)->toBe(['sector' => 'Retail'])
        ->and($enquiry->source_url)->toBe('https://partner-site.example/contact')
        ->and($enquiry->consent_given)->toBeTrue()
        ->and($response->json('reference'))->toBe($enquiry->id);

    Notification::assertSentOnDemand(EnquiryAcknowledgement::class);
});

it('validates like the website form', function (): void {
    config(['lite-crm.enquiry_api.enabled' => true]);
    $token = app(ApiToken::class)->rotate();

    $this->withToken($token)
        ->postJson('/crm-api/enquiries', enquiryPayload(['email' => 'not-an-address', 'type' => 'no-such-type']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'type']);
});

it('applies the honeypot and timing checks when the sender forwards them', function (array $signals, string $reason): void {
    config(['lite-crm.enquiry_api.enabled' => true]);
    $token = app(ApiToken::class)->rotate();

    $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload($signals))->assertStatus(202);

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->status)->toBe(EnquiryStatus::Spam)
        ->and($enquiry->spam_reason)->toBe($reason);

    Notification::assertNothingSent();
})->with([
    'honeypot' => [['_honeypot' => 'http://spam.example'], 'honeypot'],
    'too fast' => [fn () => ['_started_at' => now()->getTimestamp()], 'too_fast'],
]);

it('limits enquiries per IP address', function (): void {
    config(['lite-crm.enquiry_api.enabled' => true, 'lite-crm.enquiries.rate_limit.attempts' => 2]);
    $token = app(ApiToken::class)->rotate();

    $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload())->assertStatus(202);
    $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload())->assertStatus(202);
    $this->withToken($token)->postJson('/crm-api/enquiries', enquiryPayload())->assertTooManyRequests();

    expect(Enquiry::query()->count())->toBe(2);
});

it('stores only a hash of the token, and rotation revokes the old one', function (): void {
    config(['lite-crm.enquiry_api.enabled' => true]);
    $tokens = app(ApiToken::class);

    $old = $tokens->rotate();
    $new = $tokens->rotate();

    expect(app(CrmSettings::class)->get(ApiToken::HASH))->toBe(hash('sha256', $new))->not->toContain($new);

    $this->withToken($old)->postJson('/crm-api/enquiries', enquiryPayload())->assertUnauthorized();
    $this->withToken($new)->postJson('/crm-api/enquiries', enquiryPayload())->assertStatus(202);

    $tokens->revoke();
    $this->withToken($new)->postJson('/crm-api/enquiries', enquiryPayload())->assertUnauthorized();
});
