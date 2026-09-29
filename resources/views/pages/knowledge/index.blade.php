{{-- Knowledge Centre (v3 §7.9): published articles only. --}}
<x-layouts.site
    title="Insights — Knowledge Centre — AviNutra"
    description="Technical articles on poultry nutrition, feed ingredients, feed economics and ingredient quality, and a glossary of feed terms."
>
    <x-page.hero
        eyebrow="Knowledge Centre"
        title="Insights"
        lead="Technical articles for feed manufacturers and nutritionists, each with its author or reviewer, sources and last-reviewed date."
        :breadcrumbs="['Insights' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[2fr_1fr] xl:px-8">
            <div>
                @if ($categories->count() > 1)
                    <nav aria-label="Categories" class="mb-8">
                        <ul class="flex flex-wrap gap-2">
                            <li><a href="{{ route('knowledge') }}" @if (! $category) aria-current="true" @endif @class(['inline-block rounded-full border-2 px-4 py-2 text-sm font-semibold', 'border-green-800 bg-green-800 text-white' => ! $category, 'border-line text-green-900 hover:border-green-700' => $category])>All</a></li>
                            @foreach ($categories as $case)
                                <li><a href="{{ route('knowledge', ['category' => $case->value]) }}" @if ($category === $case) aria-current="true" @endif @class(['inline-block rounded-full border-2 px-4 py-2 text-sm font-semibold', 'border-green-800 bg-green-800 text-white' => $category === $case, 'border-line text-green-900 hover:border-green-700' => $category !== $case])>{{ $case->getLabel() }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($articles->isEmpty())
                    <p class="text-lg text-muted">New articles are being reviewed by our nutrition team.</p>
                @else
                    <ul class="space-y-6">
                        @foreach ($articles as $article)
                            <li class="card">
                                <p class="eyebrow">{{ $article->category->getLabel() }}</p>
                                <h2 class="mt-2 text-2xl leading-snug"><a href="{{ route('knowledge.show', $article->slug) }}" class="hover:text-green-700 hover:underline">{{ $article->title }}</a></h2>
                                @if ($article->summary)
                                    <p class="mt-3 text-base text-muted">{{ $article->summary }}</p>
                                @endif
                                <p class="mt-4 text-sm text-muted">
                                    {{ $article->authorName() }}@if ($article->reviewerName()) · Reviewed by {{ $article->reviewerName() }}@endif
                                    @if ($article->last_reviewed_on) · Last reviewed {{ $article->last_reviewed_on->format('j F Y') }}@endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside class="space-y-6 self-start">
                @if ($termCount > 0)
                    <a href="{{ route('knowledge.glossary') }}" class="card group block transition hover:border-green-700">
                        <p class="eyebrow">Reference</p>
                        <h2 class="mt-2 text-2xl">Glossary</h2>
                        <p class="mt-2 text-base text-muted">{{ $termCount }} feed and nutrition terms explained.</p>
                        <span class="mt-4 inline-block font-semibold text-green-700 group-hover:underline">Open the glossary →</span>
                    </a>
                @endif
                <div class="rounded-(--radius-card) bg-surface p-7 text-base text-muted">
                    How we write and review content: <a href="{{ route('about.editorial-policy') }}" class="font-semibold text-green-700 underline">Editorial Policy</a>.
                </div>
            </aside>
        </div>
    </section>

    <x-page.cta-band title="Have a technical question?" label="Ask Our Nutrition Team" />
</x-layouts.site>
