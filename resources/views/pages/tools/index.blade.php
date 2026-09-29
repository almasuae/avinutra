{{--
    Tools index (v3 §7.8, as amended 29 Sep 2026): the live tools, plus at most two
    "Coming soon" cards. Later tools are not listed until they are planned.
--}}
@php
    $tools = collect([
        ['tools.methionine-value', 'Methionine Value Calculator', 'Compare 2 to 4 methionine sources on cost per kg of effective methionine, cost per tonne of feed, and monthly and annual differences.', 'heroicon-o-beaker'],
        ['tools.landed-cost', 'Landed Cost Calculator', 'Landed cost per kg, per tonne and per shipment of an imported feed additive, in any currency, with a line-by-line breakdown of duties, taxes and charges.', 'heroicon-o-truck'],
    ])
        ->map(fn (array $tool): array => [...$tool, \App\Support\SiteLinks::url($tool[0])])
        ->filter(fn (array $tool): bool => $tool[4] !== null);
    $comingSoon = [
        ['Feed Cost Impact Calculator', 'The cost per tonne of feed, and the monthly and annual saving, of changing an ingredient\'s price or inclusion.', 'heroicon-o-calculator'],
        ['FCR Economics Calculator', 'Feed cost per bird and per kg of live weight, and the value of an improvement in feed conversion.', 'heroicon-o-scale'],
    ];
@endphp
<x-layouts.site
    title="Tools & Calculators — AviNutra"
    description="Feed calculators for poultry feed manufacturers: methionine value and landed cost, with every formula shown."
>
    <x-page.hero
        eyebrow="Tools"
        title="Tools & Calculators"
        lead="Practical calculators for feed manufacturers, with every formula shown. Compare products on value, not price per tonne."
        :breadcrumbs="['Tools' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($tools as [$route, $title, $text, $icon, $href])
                    <li class="card flex flex-col">
                        <span class="flex size-14 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                            <x-dynamic-component :component="$icon" class="size-7" aria-hidden="true" />
                        </span>
                        <h2 class="mt-5 text-xl">{{ $title }}</h2>
                        <p class="mt-2 flex-1 text-base text-muted">{{ $text }}</p>
                        <a href="{{ $href }}" class="btn-primary mt-6 self-start">Open the tool <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                    </li>
                @endforeach
                @foreach ($comingSoon as [$title, $text, $icon])
                    <li class="card flex flex-col bg-surface">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex size-14 items-center justify-center rounded-full border-2 border-line text-muted">
                                <x-dynamic-component :component="$icon" class="size-7" aria-hidden="true" />
                            </span>
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-muted">Coming soon</span>
                        </div>
                        <h2 class="mt-5 text-xl">{{ $title }}</h2>
                        <p class="mt-2 flex-1 text-base text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <x-tools.technical-notice />
        </div>
    </section>

    <x-page.cta-band title="Want to discuss a calculation?" text="Our nutrition team can help you interpret a comparison for your formulation." label="Talk to a Nutritionist" />
</x-layouts.site>
