<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\GlossaryTerm;
use App\Settings\SiteSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use LiteCrm\LiteCrm;
use LiteCrm\Notifications\EnquiryAcknowledgement;

/*
 * Owner's decision of 29 Sep 2026: the public website names no country and shows
 * no company-status details. Only the ISO country picker's option list (a complete
 * list of countries, where visitors choose their own) is exempt.
 */

uses(RefreshDatabase::class);

const FORBIDDEN_COUNTRY_WORDS = '/\b(Singapore|Pakistan|Pakistani|PKR|Karachi|Lahore|Punjab|Sindh|SBP|FBR)\b|Pte\.?\s*Ltd/i';

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);

    // Fill every company-status setting, so a leak would show.
    $site = app(SiteSettings::class);
    $site->sg_incorporated = true;
    $site->legal_name = 'Example Holdings Pte. Ltd.';
    $site->uen = '202600001A';
    $site->registered_office = '1 Example Road, Singapore';
    $site->pk_partner_name = 'Example Traders';
    $site->pk_partner_city = 'Lahore';
    $site->pk_partner_role = 'importer of record in Pakistan';
    $site->save();
});

/**
 * The page's HTML without the country picker's option list.
 */
function publicHtml(string $html): string
{
    return (string) preg_replace('#<datalist\b[^>]*>.*?</datalist>#s', '', $html);
}

it('never shows a country or company-status word on a public page', function (string $path): void {
    $html = publicHtml((string) $this->get($path)->getContent());

    expect(preg_match(FORBIDDEN_COUNTRY_WORDS, $html, $match))->toBe(0, 'Found "'.($match[0] ?? '')."\" on {$path}");
})->with([
    '/', '/about', '/about/company', '/about/editorial-policy',
    '/nutrition-services', '/nutrition-services/formulation-support', '/nutrition-services/feed-economics',
    '/nutrition-services/feed-mills', '/nutrition-services/request-sourcing',
    '/ingredients', '/ingredients/amino-acids', '/ingredients/amino-acids/methionine',
    '/quality', '/suppliers', '/suppliers/how-we-work', '/suppliers/apply',
    '/tools', '/tools/methionine-value', '/tools/landed-cost',
    '/knowledge', '/knowledge/glossary', '/knowledge/how-to-compare-methionine-sources',
    '/contact', '/contact?type=quotation', '/contact?type=supplier_application', '/contact?type=call', '/ask-a-nutritionist',
    '/legal/privacy', '/legal/terms', '/legal/cookies', '/legal/technical-disclaimer',
    '/this-page-does-not-exist',
]);

it('keeps the country words out of the seeded content', function (): void {
    $articles = Article::query()->withTrashed()->get()->map(fn (Article $a): string => $a->title.' '.$a->summary.' '.$a->body.' '.$a->outline)->implode(' ');
    $glossary = GlossaryTerm::query()->get()->map(fn (GlossaryTerm $t): string => $t->term.' '.$t->definition)->implode(' ');

    expect(preg_match(FORBIDDEN_COUNTRY_WORDS, $articles))->toBe(0)
        ->and(preg_match(FORBIDDEN_COUNTRY_WORDS, $glossary))->toBe(0);
});

it('keeps the country words out of the enquiry acknowledgement e-mail', function (): void {
    $enquiry = LiteCrm::captureEnquiry(['email' => 'buyer@example.com', 'message' => 'Hello'], 'general');
    $mail = (string) (new EnquiryAcknowledgement($enquiry))->toMail(new AnonymousNotifiable)->render();

    expect(preg_match(FORBIDDEN_COUNTRY_WORDS, $mail))->toBe(0);
});

it('still offers every country in the country picker', function (): void {
    $this->get('/contact')->assertSee('<datalist', false)->assertSee('data-code="DE"', false);
});
