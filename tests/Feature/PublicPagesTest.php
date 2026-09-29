<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\TeamRole;
use App\Models\Article;
use App\Models\GlossaryTerm;
use App\Models\PageSeo;
use App\Models\TeamProfile;
use App\Settings\SiteSettings;
use App\Support\EnquiryForms;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Models\Enquiry;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

dataset('public pages', [
    '/', '/about', '/about/company', '/about/editorial-policy',
    '/nutrition-services', '/nutrition-services/formulation-support', '/nutrition-services/ingredient-evaluation',
    '/nutrition-services/product-substitution', '/nutrition-services/feed-economics', '/nutrition-services/supplier-qualification',
    '/nutrition-services/technical-trials', '/nutrition-services/feed-mills', '/nutrition-services/request-sourcing',
    '/ingredients', '/ingredients/amino-acids', '/ingredients/enzymes', '/ingredients/vitamins-minerals',
    '/ingredients/mycotoxin-management', '/ingredients/gut-health', '/ingredients/specialty-additives',
    '/ingredients/amino-acids/methionine', '/quality', '/suppliers', '/suppliers/how-we-work', '/suppliers/apply',
    '/tools', '/tools/methionine-value', '/tools/landed-cost', '/knowledge', '/knowledge/glossary', '/knowledge/how-to-compare-methionine-sources',
    '/contact', '/ask-a-nutritionist',
    '/legal/privacy', '/legal/terms', '/legal/cookies', '/legal/technical-disclaimer',
]);

it('serves every public page with one H1 and no placeholders, claims or trademarks', function (string $path): void {
    $html = (string) $this->get($path)->assertOk()->getContent();
    $text = strip_tags(preg_replace('#<(script|style)\b.*?</\1>#s', '', $html) ?? '');

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($text)->not->toMatch('/lorem ipsum|\bTODO\b|\bTBD\b|placeholder/i')
        ->and($text)->not->toMatch('/\b(prevents?|cures?|treats?)\b/i')
        ->and($text)->not->toMatch('/Pte\.? Ltd/i')
        ->and($text)->not->toMatch('/MetAMINO|Rhodimet|Sandimet|ADRY|Novus|Evonik|Adisseo/i')
        ->and($text)->not->toMatch('/world-class|best quality/i');
})->with('public pages');

it('shows the homepage sections from the design brief', function (): void {
    $this->get('/')->assertOk()->assertSeeInOrder([
        'Poultry Nutrition Expertise. Global Feed Ingredient Supply.',
        'Our Services', 'Contact Us',
        'Feed Nutrition', 'Consulting', 'Ingredient Supply', 'International Reach',
        'I run or supply a feed mill', 'I manufacture feed ingredients',
        'Better Nutrition. Stronger Poultry. Better Economics.',
        'Documented Quality', 'Global Sourcing', 'Technical First',
        'Feed Mills Need More Than a Supplier.',
        'Why AviNutra',
        'Precision Amino Acid Nutrition',
        'Latest Insights',
        'Have a formulation or sourcing question?',
    ]);
});

it('hides Latest Insights while no article is published', function (): void {
    Article::query()->update(['status' => ArticleStatus::Draft->value]);

    $this->get('/')->assertOk()->assertDontSee('Latest Insights');
});

it('lists the live tools and at most two coming-soon tools', function (): void {
    $html = (string) $this->get('/tools')->assertOk()->getContent();

    expect(substr_count($html, 'Coming soon'))->toBe(2)
        ->and($html)->toContain(route('tools.methionine-value'))
        ->and($html)->toContain(route('tools.landed-cost'))
        ->and($html)->toContain('Feed Cost Impact Calculator')
        ->and($html)->toContain('FCR Economics Calculator')
        ->and($html)->not->toContain('Amino Acid Value')
        ->and($html)->not->toContain('Methionine Requirement');

    $this->get('/')->assertSee('Try the Methionine Value Calculator');
});

it('says Coming soon only on the Tools page', function (): void {
    foreach (['/', '/ingredients', '/knowledge'] as $path) {
        $this->get($path)->assertDontSee('Coming soon');
    }
});

it('shows only published articles, with a person named only when their profile is public', function (): void {
    $this->get('/knowledge/mha-vs-dl-methionine')->assertNotFound();

    $person = TeamProfile::query()->create(['name' => 'Dr Example Person', 'role_type' => TeamRole::Adviser]);
    $article = Article::query()->where('slug', 'how-to-compare-methionine-sources')->sole();
    $article->update(['author_id' => $person->getKey()]);

    $this->get('/knowledge/'.$article->slug)->assertOk()->assertSee(Article::COMPANY_AUTHOR)->assertDontSee('Dr Example Person');

    $person->update(['consent_on_file' => true, 'consent_date' => now(), 'is_published' => true]);
    $this->get('/knowledge/'.$article->slug)->assertOk()->assertSee('Dr Example Person');
});

it('publishes the team page only when a profile has consent on file', function (): void {
    $this->get('/about')->assertSee('Our nutrition advisory panel profiles will be published shortly.');
    $this->get('/about/team')->assertNotFound();

    $profile = TeamProfile::query()->create(['name' => 'Dr Example Adviser', 'role_type' => TeamRole::Adviser, 'is_published' => true]);
    $this->get('/about/team')->assertNotFound();

    $profile->update(['consent_on_file' => true, 'consent_date' => now()]);
    $this->get('/about/team')->assertOk()->assertSee('Dr Example Adviser')->assertSee('Adviser');
    $this->get('/about')->assertSee(route('about.team'), false);
});

it('serves the glossary with at least 20 terms, and hides it when empty', function (): void {
    expect(GlossaryTerm::query()->published()->count())->toBeGreaterThanOrEqual(20);
    $this->get('/knowledge/glossary')->assertOk()->assertSee('Value factor');

    GlossaryTerm::query()->update(['is_published' => false]);
    $this->get('/knowledge/glossary')->assertNotFound();
});

it('keeps articles 2 to 8 as drafts with outlines', function (): void {
    $drafts = Article::query()->where('status', ArticleStatus::Draft->value)->get();

    expect($drafts)->toHaveCount(7)
        ->and($drafts->every(fn (Article $article): bool => filled($article->outline)))->toBeTrue()
        ->and(Article::query()->published()->count())->toBe(1);
});

it('uses the Page SEO title and description when set', function (): void {
    PageSeo::query()->create(['route_name' => 'quality', 'title' => 'Feed ingredient quality', 'description' => 'How we control quality.']);

    $this->get('/quality')->assertSee('<title>Feed ingredient quality</title>', false)->assertSee('How we control quality.');
});

it('says only that every quotation states the contracting entity', function (): void {
    $this->get('/about/company')->assertSee('Every quotation states the contracting legal entity')->assertDontSee('Legal status');
    $this->get('/contact')->assertDontSee('Where we are')->assertSee('info@avinutra.com');
});

it('shows WhatsApp and Request a Call only once a number is set', function (): void {
    $this->get('/contact')->assertDontSee('WhatsApp Sales')->assertDontSee('Request a call');

    $site = app(SiteSettings::class);
    $site->whatsapp_sales = '+92 300 0000000';
    $site->save();

    $this->get('/contact')->assertSee('WhatsApp Sales')->assertSee('https://wa.me/923000000000', false)->assertSee('Request a call');
    $this->get('/')->assertSee('WhatsApp us');
});

it('picks the contact form from the enquiry type', function (): void {
    $this->get('/contact?type=supplier_application')->assertSee('Supplier partnership')->assertSee('Send application');
    $this->get('/contact?type=nonsense')->assertSee('Send message');
    $this->get('/contact?type=document&product=DL-Methionine&documents_needed=COA')->assertSee('DL-Methionine')->assertSee('Request documents');
});

it('pre-selects the topic on Ask a Nutritionist', function (): void {
    $this->get('/ask-a-nutritionist?topic=feed-economics')->assertOk()->assertSee('Send my question');
});

it('renders the branded 404 page', function (): void {
    $this->get('/no-such-page')->assertNotFound()->assertSee('This page could not be found');
});

it('turns a website form submission into a CRM enquiry of the right type', function (string $form, string $mailbox): void {
    Notification::fake();
    $settings = EnquiryForms::form($form);
    $data = collect($settings['fields'])->keys()->mapWithKeys(fn (string $key): array => [$key => match ($key) {
        'email' => 'buyer@example.com',
        'country' => 'DE',
        'species' => 'broiler',
        'topic' => 'feed-economics',
        default => 'Test '.$key,
    }])->all();

    $component = Livewire\Livewire::test('lite-crm.enquiry-form', [
        'type' => $settings['type'], 'fields' => $settings['fields'], 'uploads' => $settings['uploads'],
    ]);
    $this->travel(10)->seconds();

    $component->set('data', $data)->set('consent', true)->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    $enquiry = Enquiry::query()->sole();
    expect($enquiry->type?->key)->toBe($settings['type'])
        ->and($enquiry->type?->meta['mailbox'] ?? null)->toBe($mailbox);
})->with([
    'ask a nutritionist' => ['ask_nutritionist', 'nutrition@avinutra.com'],
    'sourcing request' => ['sourcing_request', 'sales@avinutra.com'],
    'supplier application' => ['supplier_application', 'partners@avinutra.com'],
    'general' => ['general', 'info@avinutra.com'],
]);
