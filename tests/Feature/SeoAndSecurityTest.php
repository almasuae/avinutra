<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Models\Article;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/**
 * @return list<array<string, mixed>>
 */
function jsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $matches);

    return array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

it('sends the security headers on public pages, with a nonce-based script policy', function (): void {
    $response = $this->get('/');
    $csp = (string) $response->headers->get('Content-Security-Policy');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Permissions-Policy')
        ->assertHeaderMissing('Strict-Transport-Security'); // only over HTTPS in production

    preg_match("/'nonce-([^']+)'/", $csp, $nonce);

    expect($csp)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->not->toContain("script-src 'self' 'unsafe-inline'")
        ->not->toMatch('#https?://#')
        ->and($nonce[1] ?? null)->not->toBeNull()
        ->and((string) $response->getContent())->toContain('nonce="'.$nonce[1].'"');
});

it('lets the CRM run its inline scripts, still from this site only', function (): void {
    $csp = (string) $this->get('/crm/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'")
        ->not->toMatch('#https?://#');
});

it('sends HSTS over HTTPS in production', function (): void {
    app()->instance('env', 'production');

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('leaves HSTS to the web server when SECURITY_HSTS is false', function (): void {
    app()->instance('env', 'production');
    config(['security.hsts' => false]);

    $this->get('https://localhost/')->assertHeaderMissing('Strict-Transport-Security');
});

it('keeps the CRM out of search engines', function (): void {
    $robots = (string) $this->get('/robots.txt')->getContent();

    expect($robots)->toContain('Disallow: /crm')->toContain('Sitemap: '.url('/sitemap.xml'));
    $this->get('/crm/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('lists the public pages in the sitemap, and never the CRM or unpublished content', function (): void {
    $xml = (string) $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
    $locations = simplexml_load_string($xml)->url;
    $urls = collect(iterator_to_array($locations, false))->map(fn ($url): string => (string) $url->loc);

    expect($urls)->toContain(url('/'))
        ->toContain(route('tools.landed-cost'))
        ->toContain(route('services.show', 'feed-economics'))
        ->toContain(route('ingredients.category', 'amino-acids'))
        ->toContain(route('knowledge.show', 'how-to-compare-methionine-sources'))
        ->toContain(route('knowledge.glossary'))
        ->and($urls->filter(fn (string $url): bool => str_contains($url, '/crm') || str_contains($url, '/dev/')))->toBeEmpty()
        ->and($urls->filter(fn (string $url): bool => str_contains($url, 'mha-vs-dl-methionine')))->toBeEmpty() // a draft
        ->and($urls->filter(fn (string $url): bool => str_contains($url, '/about/team')))->toBeEmpty(); // no public profile yet
});

it('describes the organisation, breadcrumbs and articles as structured data', function (): void {
    $home = jsonLd((string) $this->get('/')->getContent());
    expect($home[0]['@graph'][0]['@type'])->toBe('Organization')
        ->and($home[0]['@graph'][0])->not->toHaveKey('address')
        ->and($home[0]['@graph'][1]['@type'])->toBe('WebSite');

    $page = jsonLd((string) $this->get('/nutrition-services/feed-economics')->getContent());
    $breadcrumbs = collect($page)->firstWhere('@type', 'BreadcrumbList');
    expect(array_column($breadcrumbs['itemListElement'], 'name'))->toBe(['Home', 'Nutrition Services', 'Feed Economics']);

    $article = collect(jsonLd((string) $this->get('/knowledge/how-to-compare-methionine-sources')->getContent()))->firstWhere('@type', 'Article');
    expect($article['headline'])->toBe('How to Compare Methionine Sources: Cost per kg of Effective Methionine')
        ->and($article['author']['name'])->toBe(Article::COMPANY_AUTHOR);
});

it('keeps Open Graph and canonical tags on every page', function (): void {
    $this->get('/quality')
        ->assertSee('<link rel="canonical" href="'.url('/quality').'">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $this->get('/knowledge/how-to-compare-methionine-sources')->assertSee('<meta property="og:type" content="article">', false);
});

it('renders the maintenance and error pages without the database or built assets', function (): void {
    $html = view('errors.503')->render();

    expect($html)->toContain('We will be back shortly')
        ->not->toContain('/build/')
        ->and(view('errors.500')->render())->toContain('Something went wrong');
});

it('backs up the database and uploaded files to the server\'s own backups disk', function (): void {
    expect(config('backup.backup.destination.disks'))->toBe(['backups'])
        ->and(config('filesystems.disks.backups.root'))->toBe(storage_path('app/backups'))
        ->and(config('filesystems.disks.backups.driver'))->toBe('local')
        ->and(config('backup.backup.source.files.include'))->toBe([storage_path('app/private'), storage_path('app/public')])
        ->and(config('backup.backup.source.databases'))->toContain(config('database.default'))
        ->and(config('filesystems.disks.local.serve'))->toBeFalse();

    $commands = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");
    expect($commands)->toContain('backup:run')->toContain('backup:clean')->toContain('backup:monitor');
});

it('never runs a destructive command in deploy.sh', function (): void {
    $script = (string) file_get_contents(base_path('deploy.sh'));
    $code = implode("\n", array_filter(explode("\n", $script), fn (string $line): bool => ! str_starts_with(ltrim($line), '#')));

    foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe', 'db:seed', 'reset --hard', 'git clean', 'git push', '--force-with-lease', 'rm -rf', 'rm -r ', 'storage:unlink'] as $forbidden) {
        expect($code)->not->toContain($forbidden);
    }

    expect($code)->toContain('set -Eeuo pipefail')
        ->toContain('git pull --ff-only')
        ->toContain('artisan migrate --force')
        ->toContain('install --no-dev --optimize-autoloader')
        ->not->toMatch('/\brm\b.*storage/');
});

it('only publishes articles that are published', function (): void {
    Article::query()->where('slug', 'mha-vs-dl-methionine')->update(['status' => ArticleStatus::InReview->value]);

    expect((string) $this->get('/sitemap.xml')->getContent())->not->toContain('mha-vs-dl-methionine');
});
