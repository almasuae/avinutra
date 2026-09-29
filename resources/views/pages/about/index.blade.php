{{-- About (v3 §7.2). --}}
<x-layouts.site
    title="About AviNutra"
    description="A specialist feed-nutrition and ingredient-sourcing company helping poultry and animal-feed businesses make better technical and commercial decisions."
>
    <x-page.hero
        eyebrow="About us"
        title="About AviNutra"
        lead="A specialist feed-nutrition and ingredient-sourcing company helping poultry and animal-feed businesses make better technical and commercial decisions."
        :breadcrumbs="['About' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 xl:px-8">
            <div>
                <x-page.heading eyebrow="Philosophy" title="Technical knowledge before commercial recommendation." />
                <p class="mt-6 text-lg">
                    We are a feed-nutrition company that uses international sourcing and trading capability to deliver nutrition solutions — not a trading company that happens to know nutrition. Every recommendation starts with the nutritional and economic facts.
                </p>
                <p class="mt-4 text-lg">
                    Our initial focus is the poultry feed industry in Pakistan. We work with feed manufacturers on formulation and feed economics, and with established international manufacturers on reliable, well-documented supply.
                </p>
            </div>
            <div class="card self-start">
                <p class="eyebrow">Our team</p>
                <h2 class="mt-2 text-2xl">Led by a nutrition advisory panel</h2>
                <p class="mt-3 text-base text-muted">AviNutra is led by a nutrition advisory panel and supported by an international sourcing and trading function.</p>
                @if ($hasTeam)
                    <a href="{{ route('about.team') }}" class="btn-secondary mt-6">Meet the team</a>
                @else
                    <p class="mt-3 text-base text-muted">Our nutrition advisory panel profiles will be published shortly.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="Our approach" title="Understand. Evaluate. Validate. Supply. Support." />
            <x-page.flow class="mt-8" label="Our approach" :steps="['Understand', 'Evaluate', 'Validate', 'Supply', 'Support']" />
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-6 px-4 py-16 sm:px-6 md:grid-cols-2 xl:px-8">
            <a href="{{ route('about.company') }}" class="card group transition hover:border-green-700">
                <p class="eyebrow">Transparency</p>
                <h2 class="mt-2 text-2xl">Company</h2>
                <p class="mt-3 text-base text-muted">Our legal status, our partner in Pakistan, who contracts, and our commercial relationships.</p>
                <span class="mt-5 inline-block font-semibold text-green-700 group-hover:underline">Read more →</span>
            </a>
            <a href="{{ route('about.editorial-policy') }}" class="card group transition hover:border-green-700">
                <p class="eyebrow">Content standards</p>
                <h2 class="mt-2 text-2xl">Editorial Policy</h2>
                <p class="mt-3 text-base text-muted">Who writes and reviews our content, how we cite sources, and how we handle corrections.</p>
                <span class="mt-5 inline-block font-semibold text-green-700 group-hover:underline">Read more →</span>
            </a>
        </div>
    </section>

    <x-page.cta-band />
</x-layouts.site>
