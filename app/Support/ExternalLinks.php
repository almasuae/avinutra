<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Link rule for the public site (CLAUDE.md, website content):
 *  - external links (another host) open in a new tab with target="_blank",
 *    rel="noopener noreferrer", a small "opens in new tab" icon and screen-reader text;
 *  - internal links (relative, this site's hosts) and mailto:/tel: links stay in the same tab.
 *
 * Applied to article bodies written in the CRM and, through the ProcessExternalLinks
 * middleware, to every public HTML page, so templates and future content are covered.
 * Processing is idempotent.
 */
class ExternalLinks
{
    public const MARKER = 'data-external-link';

    /** An <a> start tag (quoted attribute values may contain ">"), its content and the end tag. */
    private const PATTERN = '~<a\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>(.*?)</a\s*>~is';

    public static function process(string $html): string
    {
        if (stripos($html, '<a') === false) {
            return $html;
        }

        $hosts = static::internalHosts();

        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($hosts): string {
            [$whole, $attributes, $content] = $match;

            $href = self::attribute($attributes, 'href');

            if ($href === null) {
                return $whole;
            }

            if (! static::isExternal($href, $hosts)) {
                // Internal links stay in the same tab.
                return self::attribute($attributes, 'target') === null
                    ? $whole
                    : '<a'.self::withoutAttribute($attributes, 'target').'>'.$content.'</a>';
            }

            $rel = array_filter(preg_split('/\s+/', (string) self::attribute($attributes, 'rel')) ?: []);
            $rel = array_values(array_unique([...$rel, 'noopener', 'noreferrer']));

            $attributes = self::withoutAttribute($attributes, 'target');
            $attributes = self::withoutAttribute($attributes, 'rel');
            $attributes = rtrim($attributes).' target="_blank" rel="'.implode(' ', $rel).'"';

            if (! str_contains($content, self::MARKER)) {
                $content .= static::icon();
            }

            return '<a'.$attributes.'>'.$content.'</a>';
        }, $html);
    }

    /**
     * @param  list<string>  $hosts
     */
    public static function isExternal(string $href, array $hosts): bool
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5));

        if (str_starts_with($href, '//')) {
            $href = 'https:'.$href;
        }

        if (! preg_match('~^https?://~i', $href)) {
            // Relative links, fragments, mailto:, tel: and the like.
            return false;
        }

        $host = strtolower((string) parse_url($href, PHP_URL_HOST));

        return $host !== '' && ! in_array($host, $hosts, true);
    }

    /**
     * @return list<string>
     */
    public static function internalHosts(): array
    {
        $hosts = (array) config('site.internal_hosts', []);
        $hosts[] = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (app()->bound('request')) {
            $hosts[] = request()->getHost();
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $host): string => strtolower((string) $host),
            $hosts,
        ))));
    }

    public static function icon(): string
    {
        return '<svg '.self::MARKER.' class="ml-0.5 inline-block size-3.5 align-[-0.125em]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">'
            .'<path d="M11 3a1 1 0 1 0 0 2h2.59l-6.3 6.29a1 1 0 1 0 1.42 1.42L15 6.41V9a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1h-5Z"/>'
            .'<path d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 0 0 0-2H5Z"/>'
            .'</svg><span class="sr-only"> (opens in new tab)</span>';
    }

    private static function attribute(string $attributes, string $name): ?string
    {
        if (preg_match('~\s'.$name.'\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>"\']+))~i', $attributes, $m)) {
            return ($m[1] ?? '') !== '' ? $m[1] : (($m[2] ?? '') !== '' ? $m[2] : ($m[3] ?? ''));
        }

        return null;
    }

    private static function withoutAttribute(string $attributes, string $name): string
    {
        return (string) preg_replace('~\s'.$name.'\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>"\']+)~i', '', $attributes);
    }
}
