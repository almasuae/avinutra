{{-- For Suppliers — Partner With Us (v3 §7.7; no country references on the public site). --}}
@php
    $offer = [
        ['heroicon-o-chart-bar', 'Market intelligence'],
        ['heroicon-o-user-group', 'Customer identification and targeting'],
        ['heroicon-o-academic-cap', 'Technical representation by nutritionists'],
        ['heroicon-o-beaker', 'Product trials'],
        ['heroicon-o-document-text', 'Registration and documentation coordination'],
        ['heroicon-o-building-storefront', 'Distributor and stockist development'],
        ['heroicon-o-presentation-chart-line', 'Commercial development'],
        ['heroicon-o-lifebuoy', 'Customer support'],
        ['heroicon-o-arrow-path', 'Structured market feedback'],
    ];
@endphp
<x-layouts.site
    title="For Suppliers — Partner With Us — AviNutra"
    description="AviNutra works with feed-ingredient manufacturers seeking technical market development, qualified distribution and customer support in selected international markets."
>
    <x-page.hero
        eyebrow="For suppliers"
        title="Partner With Us"
        lead="We work with feed-ingredient manufacturers seeking technical market development, qualified distribution and customer support in selected international markets."
        :breadcrumbs="['For Suppliers' => null]"
    >
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ route('suppliers.apply') }}" class="btn-primary">Become a Supply Partner <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
            <a href="{{ route('suppliers.how-we-work') }}" class="btn-secondary">How we work</a>
        </div>
    </x-page.hero>

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="What we offer manufacturers" title="Technical market development, not just distribution" />
            <p class="mt-6 max-w-3xl text-lg">Feed mills buy from people who understand their formulation, procurement and cost problems. Our nutritionists represent your product technically, so it is judged on its real value.</p>
            <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($offer as [$icon, $title])
                    <li class="flex items-center gap-4 rounded-(--radius-card) border border-line bg-white p-5 shadow-(--shadow-soft)">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full border-2 border-orange-500 text-orange-text">
                            <x-dynamic-component :component="$icon" class="size-6" aria-hidden="true" />
                        </span>
                        <span class="font-heading text-lg font-black text-green-900">{{ $title }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto grid max-w-[84rem] gap-6 px-4 py-16 sm:px-6 md:grid-cols-2 xl:px-8">
            <a href="{{ route('suppliers.how-we-work') }}" class="card group transition hover:border-green-700">
                <p class="eyebrow">Onboarding</p>
                <h2 class="mt-2 text-2xl">How we work</h2>
                <p class="mt-3 text-base text-muted">From product review and due diligence to launch and market reporting.</p>
                <span class="mt-5 inline-block font-semibold text-green-700 group-hover:underline">See the steps →</span>
            </a>
            <div class="card">
                <p class="eyebrow">Market information</p>
                <h2 class="mt-2 text-2xl">Detailed market analysis</h2>
                <p class="mt-3 text-base text-muted">Detailed market analysis is available to qualified manufacturers under NDA.</p>
                <a href="{{ route('suppliers.apply') }}" class="mt-5 inline-block font-semibold text-green-700 hover:underline">Apply →</a>
            </div>
        </div>
    </section>

    <x-page.cta-band
        title="Build your brand with a technical partner"
        text="Tell us about your products, capacity and the markets you want to develop."
        label="Become a Supply Partner"
        route="suppliers.apply"
    />
</x-layouts.site>
