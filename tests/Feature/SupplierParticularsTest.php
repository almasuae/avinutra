<?php

declare(strict_types=1);

use App\Support\EnquiryForms;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Livewire\EnquiryForm;
use LiteCrm\Models\Enquiry;
use LiteCrm\Notifications\EnquiryAcknowledgement;
use Livewire\Livewire;

/*
 * Owner's decision (30 Sep 2026): suppliers "introduce their company" and "share their
 * company particulars"; the public wording avoids "apply" / "application". The URL
 * stays /suppliers/apply and the CRM keeps its internal type name.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('asks suppliers for their company particulars, not an application', function (): void {
    $html = (string) $this->get('/suppliers/apply')->assertOk()->getContent();
    $text = strip_tags(preg_replace('#<(script|style)\b.*?</\1>#s', '', $html) ?? '');

    expect($text)->toContain('Supplier Particulars')
        ->and($html)->toMatch('~<button[^>]*type="submit"[^>]*>\s*(<[^>]+>\s*)*Submit\b~s')
        ->and($text)->not->toMatch('/\bappl(y|ication)\b/i');

    foreach (['/suppliers', '/suppliers/how-we-work'] as $page) {
        expect(strip_tags((string) $this->get($page)->getContent()))->not->toMatch('/\bappl(y|ication)\b/i');
    }
});

it('thanks the supplier and acknowledges the particulars by e-mail', function (): void {
    Notification::fake();
    $settings = EnquiryForms::form('supplier_application');

    Livewire::test(EnquiryForm::class, [
        'type' => $settings['type'], 'fields' => $settings['fields'], 'uploads' => 0,
        'submitLabel' => $settings['submit'], 'thanksHeading' => $settings['thanks_heading'] ?? null, 'thanksText' => $settings['thanks'] ?? null,
    ])
        ->set('data.company', 'Example Manufacturing Co')
        ->set('data.country', 'DE')
        ->set('data.name', 'Test Supplier')
        ->set('data.email', 'supplier@example.com')
        ->set('data.product_categories', 'Amino acids')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Thank you for sharing your company particulars.')
        ->assertDontSee('application');

    $enquiry = Enquiry::query()->sole();

    Notification::assertSentTo(new AnonymousNotifiable, EnquiryAcknowledgement::class, function (EnquiryAcknowledgement $notification) use ($enquiry): bool {
        $mail = $notification->toMail(new AnonymousNotifiable);
        $text = $mail->subject.' '.implode(' ', array_map('strval', $mail->introLines));

        return $notification->enquiry->is($enquiry)
            && str_contains($mail->subject, 'company particulars')
            && str_contains($text, 'Thank you for introducing your company to')
            && ! preg_match('/\bappl(y|ication)\b/i', $text);
    });
});
