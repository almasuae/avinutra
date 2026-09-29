{{-- A published article (v3 §7.9): byline, reviewer, last reviewed, sources, related tool, "Ask Our Nutrition Team". --}}
@php($related = $article->related_route ? \App\Support\SiteLinks::url($article->related_route) : null)
<x-layouts.site :title="$article->title.' — AviNutra'" :description="$article->summary" type="article">
    @push('head')
        <x-seo.json-ld :data="array_filter([
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->summary,
            'url' => route('knowledge.show', $article->slug),
            'inLanguage' => 'en-GB',
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => ($article->last_reviewed_on ?? $article->updated_at)?->toAtomString(),
            'author' => $article->author?->isPublic()
                ? ['@type' => 'Person', 'name' => $article->author->name]
                : ['@type' => 'Organization', 'name' => \App\Models\Article::COMPANY_AUTHOR],
            'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('brand/logo-full@2x.png')]],
            'image' => asset('brand/og-image.png'),
        ])" />
    @endpush
    <x-page.hero
        :eyebrow="$article->category->getLabel()"
        :title="$article->title"
        :breadcrumbs="['Insights' => route('knowledge'), $article->title => null]"
    >
        <p class="mt-5 text-base text-muted">
            {{-- Names link to the person's profile on the Team page; the company byline has no link. --}}
            @if ($article->author?->isPublic())
                <a href="{{ route('about.team') }}#{{ $article->author->slug }}" class="font-semibold text-ink underline decoration-line underline-offset-4 hover:text-green-700">{{ $article->author->name }}</a>
            @else
                <span class="font-semibold text-ink">{{ $article->authorName() }}</span>
            @endif
            @if ($article->reviewer?->isPublic()) · Reviewed by <a href="{{ route('about.team') }}#{{ $article->reviewer->slug }}" class="font-semibold text-ink underline decoration-line underline-offset-4 hover:text-green-700">{{ $article->reviewer->name }}</a>@endif
            @if ($article->last_reviewed_on) · Last reviewed {{ $article->last_reviewed_on->format('j F Y') }}@endif
        </p>
    </x-page.hero>

    <article class="bg-white">
        <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
            <div class="prose-article">{!! $article->bodyHtml() !!}</div>

            @if (! empty($article->sources))
                <section aria-labelledby="sources" class="mt-12 rounded-(--radius-card) bg-surface p-7">
                    <h2 id="sources" class="text-xl">Sources</h2>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-base">
                        @foreach ($article->sources as $source)
                            <li>
                                @if (! empty($source['url']))
                                    <a href="{{ $source['url'] }}" class="text-green-700 underline" target="_blank" rel="noopener noreferrer">{{ $source['title'] ?? $source['url'] }}</a>
                                @else
                                    {{ $source['title'] ?? '' }}
                                @endif
                                @if (! empty($source['date'])) <span class="text-muted">({{ $source['date'] }})</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($related)
                <p class="mt-8"><a href="{{ $related }}" class="btn-secondary">Related tool</a></p>
            @endif
        </div>
    </article>

    <x-page.cta-band title="Questions about this topic?" label="Ask Our Nutrition Team" />
</x-layouts.site>
