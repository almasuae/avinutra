{{-- Tools index (v3 §7.8). A tool links only once its page exists; until then it is listed as "In development". --}}
@php
    $tools = [
        ['tools.methionine-value', 'Methionine Value Calculator', 'Compare 2 to 4 methionine sources on cost per kg of effective methionine, cost per tonne of feed and monthly and annual differences.', 'heroicon-o-beaker'],
        ['tools.landed-cost-pakistan', 'Landed Cost Calculator, Pakistan', 'Landed cost per kg and per tonne of an imported feed additive, in USD and PKR, with a line-by-line breakdown of duties, taxes and charges.', 'heroicon-o-truck'],
        ['tools.feed-cost-impact', 'Feed Cost Impact Calculator', 'The cost per tonne of feed, and the monthly and annual saving, of changing an ingredient\'s price or inclusion.', 'heroicon-o-calculator'],
        ['tools.fcr-economics', 'FCR Economics Calculator', 'Feed cost per bird and per kg of live weight, and the value of an improvement in feed conversion.', 'heroicon-o-scale'],
        ['tools.amino-acid-value', 'Amino Acid Value Calculator', 'Extends the methionine comparison to lysine, threonine and valine, using assays from product documentation.', 'heroicon-o-squares-plus'],
        ['tools.methionine-requirement', 'Methionine Requirement Guide', 'Indicative digestible methionine and methionine + cystine by species, bird type and phase.', 'heroicon-o-book-open'],
    ];
@endphp
<x-layouts.site
    title="Tools & Calculators — AviNutra"
    description="Feed calculators for poultry feed manufacturers: methionine value, landed cost in Pakistan, feed cost impact and FCR economics."
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
                @foreach ($tools as [$route, $title, $text, $icon])
                    @php($href = \App\Support\SiteLinks::url($route))
                    <li class="card flex flex-col">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex size-14 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                                <x-dynamic-component :component="$icon" class="size-7" aria-hidden="true" />
                            </span>
                            @unless ($href)
                                <span class="rounded-full bg-surface px-3 py-1 text-sm font-semibold text-muted">In development</span>
                            @endunless
                        </div>
                        <h2 class="mt-5 text-xl">{{ $title }}</h2>
                        <p class="mt-2 flex-1 text-base text-muted">{{ $text }}</p>
                        @if ($href)
                            <a href="{{ $href }}" class="btn-secondary mt-6 self-start">Open the tool</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <div class="rounded-(--radius-card) border-l-4 border-orange-500 bg-white p-7">
                <h2 class="text-xl">Technical Notice</h2>
                <p class="mt-3 text-base">These tools are provided for education and preliminary commercial evaluation. Results depend on product specifications, formulation assumptions, genetics, production conditions and other variables. Final formulation and feeding decisions should be made by qualified animal-nutrition professionals using validated product data. Tax and duty figures are indicative and are not tax advice.</p>
            </div>
        </div>
    </section>

    <x-page.cta-band title="Want to discuss a calculation?" text="Our nutrition team can help you interpret a comparison for your formulation." label="Talk to a Nutritionist" />
</x-layouts.site>
