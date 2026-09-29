<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Support\ExternalLinks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Link rule (CLAUDE.md, website content): external links open in a new tab with
 * rel="noopener noreferrer", an icon and screen-reader text; internal and mailto:
 * links stay in the same tab.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/**
 * Every <a href> start tag in the HTML, with its attributes.
 *
 * @return list<array{tag: string, href: string, target: ?string, rel: ?string, content: string}>
 */
function anchors(string $html): array
{
    $found = [];
    preg_match_all('~<a\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>(.*?)</a\s*>~is', $html, $matches, PREG_SET_ORDER);

    foreach ($matches as [$tag, $attributes, $content]) {
        $attribute = fn (string $name): ?string => preg_match('~\s'.$name.'\s*=\s*"([^"]*)"~i', $attributes, $m) ? $m[1] : null;

        if (($href = $attribute('href')) !== null) {
            $found[] = ['tag' => substr($tag, 0, 160), 'href' => $href, 'target' => $attribute('target'), 'rel' => $attribute('rel'), 'content' => $content];
        }
    }

    return $found;
}

it('opens external links in a new tab and keeps internal links in the same tab, on every public page', function (): void {
    // Content written in the CRM, with an external link in the body and in the sources.
    Article::query()->create([
        'title' => 'Link rule check', 'slug' => 'link-rule-check', 'category' => 'feed-ingredients',
        'status' => ArticleStatus::Published->value, 'summary' => 'Checks the link rule.',
        'body' => 'See [a study](https://example.org/study), [our tools](/tools), [home](https://avinutra.com/) and [e-mail](mailto:info@example.com).',
        'sources' => [['title' => 'Example source', 'url' => 'https://example.org/source']],
        'last_reviewed_on' => now(), 'published_at' => now(),
    ]);

    $hosts = ExternalLinks::internalHosts();
    $queue = ['/', '/knowledge/link-rule-check'];
    $seen = array_flip($queue);
    $problems = [];
    $external = 0;

    while ($queue !== [] && count($seen) < 300) {
        $path = array_shift($queue);
        $response = $this->get($path);

        if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            continue;
        }

        foreach (anchors((string) $response->getContent()) as $a) {
            if (ExternalLinks::isExternal($a['href'], $hosts)) {
                $external++;
                $rel = preg_split('/\s+/', (string) $a['rel']) ?: [];

                if ($a['target'] !== '_blank' || ! in_array('noopener', $rel, true) || ! in_array('noreferrer', $rel, true)) {
                    $problems[] = "{$path}: external link without target/rel: {$a['tag']}";
                }
                if (! str_contains($a['content'], ExternalLinks::MARKER) || ! str_contains($a['content'], '(opens in new tab)')) {
                    $problems[] = "{$path}: external link without the new-tab icon or screen-reader text: {$a['tag']}";
                }

                continue;
            }

            if ($a['target'] !== null) {
                $problems[] = "{$path}: internal link with a target: {$a['tag']}";
            }

            $url = parse_url(html_entity_decode($a['href']));
            $next = ($url['path'] ?? '');
            if (isset($url['scheme']) && ! in_array($url['scheme'], ['http', 'https'], true)) {
                continue; // mailto:, tel:
            }
            if ($next !== '' && str_starts_with($next, '/') && ! isset($seen[$next])
                && ! preg_match('~^/(crm|dev|livewire|storage|build)(/|$)~', $next)
                && ! preg_match('~\.(pdf|png|jpe?g|webp|svg|ico|xml|txt)$~i', $next)) {
                $seen[$next] = true;
                $queue[] = $next;
            }
        }
    }

    expect($problems)->toBe([])
        ->and(count($seen))->toBeGreaterThan(30)
        ->and($external)->toBeGreaterThan(2);
});

it('marks external links in rendered article bodies', function (): void {
    $html = ExternalLinks::process('<p><a href="https://example.org/x" rel="nofollow">Study</a> <a href="/tools" target="_blank">Tools</a> <a href="mailto:a@example.com">Mail</a> <a href="https://www.avinutra.com/about">About</a></p>');

    expect($html)->toContain('<a href="https://example.org/x" target="_blank" rel="nofollow noopener noreferrer">Study<svg data-external-link')
        ->and($html)->toContain('<span class="sr-only"> (opens in new tab)</span></a>')
        ->and($html)->toContain('<a href="/tools">Tools</a>')
        ->and($html)->toContain('<a href="mailto:a@example.com">Mail</a>')
        ->and($html)->toContain('<a href="https://www.avinutra.com/about">About</a>')
        ->and(ExternalLinks::process($html))->toBe($html);
});
