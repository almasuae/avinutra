<?php

declare(strict_types=1);

use App\Content\IngredientCategories;
use App\Enums\TeamRole;
use App\Models\Article;
use App\Models\TeamProfile;
use App\Support\HeroStyle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Enums\ProductAvailability;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Product;

/*
 * Owner's decision (1 Oct 2026): hero option A — the pale green band (#F2F7EF) and the
 * header line with the scroll shadow — on EVERY public page. One setting controls it
 * (site.hero_style), and every page's top section is the shared <x-page.hero-band>.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/**
 * The top section of a page must be the shared hero band, with option A on <html>.
 */
function assertHeroA(string $path, string $html): void
{
    $main = preg_match('~<main id="main"[^>]*>\s*(<[^>]+>)~', $html, $match) === 1 ? $match[1] : '';

    expect(str_contains($html, '<html lang="en-GB" data-hero="A">'))->toBeTrue("{$path}: option A is not active")
        ->and(preg_match('~^<section data-hero-band class="site-hero[\s"]~', $main))->toBe(1, "{$path}: the top section is not the shared hero band ({$main})")
        ->and(substr_count($html, 'data-hero-band'))->toBe(1, "{$path}: not exactly one hero band")
        ->and(str_contains($html, '<header data-site-header class="site-header '))->toBeTrue("{$path}: the header has no .site-header styling");
}

it('makes option A the default, with no preview switch', function (): void {
    expect(config('site.hero_style'))->toBe('A')
        ->and(HeroStyle::current())->toBe('A')
        ->and(array_keys(HeroStyle::OPTIONS))->toBe(['A', 'original']);

    app()->detectEnvironment(fn (): string => 'local');
    $this->get('/?hero=original')->assertSee('data-hero="A"', false);
});

it('gives the top section of every public page the option-A styling', function (): void {
    // Pages a crawl from the home page cannot reach until content exists.
    TeamProfile::query()->create([
        'name' => 'Dr Example Adviser', 'role_type' => TeamRole::Adviser, 'consent_on_file' => true,
        'consent_date' => now(), 'consent_document_path' => 'team-consents/x.pdf', 'is_published' => true,
    ]);
    $category = Lookup::query()->where('type', 'product_category')->where('key', 'amino_acids')->sole();
    $product = Product::query()->create([
        'name' => 'Example Product 99%', 'category_id' => $category->getKey(), 'description' => 'Example.',
        'publish_on_website' => true, 'availability' => ProductAvailability::Information,
    ]);

    $queue = array_merge(
        ['/', '/about/team', '/no-such-page'],
        Article::query()->published()->pluck('slug')->map(fn (string $slug): string => '/knowledge/'.$slug)->all(),
    );
    $seen = array_flip($queue);
    $checked = [];

    while ($queue !== []) {
        $path = array_shift($queue);
        $response = $this->get($path);

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            continue;
        }

        $html = (string) $response->getContent();
        assertHeroA($path, $html);
        $checked[] = $path;

        preg_match_all('~href="(?:'.preg_quote(url('/'), '~').')?(/[^"#?]*)"~', $html, $links);
        foreach ($links[1] as $link) {
            if (! isset($seen[$link]) && ! preg_match('~^/(crm|dev|livewire|storage|build|brand)(/|$)~', $link)
                && ! preg_match('~\.(pdf|png|jpe?g|webp|svg|ico|xml|txt|webmanifest)$~i', $link)) {
                $seen[$link] = true;
                $queue[] = $link;
            }
        }
    }

    $productPath = '/ingredients/'.IngredientCategories::slug($category->key).'/'.$product->refresh()->slug;

    expect($checked)->toContain('/', '/about', '/about/company', '/about/editorial-policy', '/about/team',
        '/nutrition-services', '/nutrition-services/feed-mills', '/nutrition-services/request-sourcing', '/nutrition-services/formulation-support',
        '/ingredients', '/ingredients/amino-acids', '/ingredients/amino-acids/methionine',
        '/suppliers', '/suppliers/apply', '/suppliers/how-we-work',
        '/knowledge', '/knowledge/glossary', '/tools', '/tools/methionine-value', '/tools/landed-cost',
        '/quality', '/contact', '/ask-a-nutritionist',
        '/legal/privacy', '/legal/terms', '/legal/cookies', '/legal/technical-disclaimer', '/no-such-page')
        ->and($checked)->toContain($productPath)
        ->and(count($checked))->toBeGreaterThanOrEqual(40);
});

it('can switch every page back to the original look in one place', function (): void {
    config(['site.hero_style' => 'original']);

    foreach (['/', '/contact', '/legal/privacy'] as $page) {
        $this->get($page)->assertSee('data-hero="original"', false);
    }

    config(['site.hero_style' => 'nonsense']);
    expect(HeroStyle::current())->toBe('A');
});

it('keeps only option A and the original look in the stylesheet', function (): void {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain("[data-hero='A']")
        ->and($css)->toContain("[data-hero='original']")
        ->and($css)->not->toMatch("~\[data-hero='[BCD]'\]~")
        ->and($css)->toContain('--color-hero-tint: #f2f7ef;')
        ->and($css)->toContain('.site-header[data-scrolled]');
});
