{{-- How we work with manufacturers (v3 §7.7). --}}
@php
    $steps = [
        ['Product review', 'We review your product range, specifications and fit with the market.'],
        ['Technical documentation', 'TDS, SDS, COA, specifications, registrations and certificates.'],
        ['Manufacturer due diligence', 'Your quality systems, capacity and supply record.'],
        ['Commercial discussion', 'Pricing structure, terms, minimum order quantities and lead times.'],
        ['Territory assessment', 'Where your products have the strongest technical and commercial case.'],
        ['Customer targeting', 'The feed mills whose formulations and volumes fit your products.'],
        ['Trial programme', 'Structured trials with customers, where a trial is needed.'],
        ['Launch', 'Introduction to customers, with technical support from our nutritionists.'],
        ['Market development and reporting', 'Regular, structured feedback on customers, competition and demand.'],
    ];
@endphp
<x-layouts.site
    title="How We Work — For Suppliers — AviNutra"
    description="How AviNutra works with feed-ingredient manufacturers: product review, documentation, due diligence, territory assessment, trials, launch and market reporting."
>
    <x-page.hero
        eyebrow="For suppliers"
        title="How we work"
        lead="A structured path from first review to market development, so both sides know what happens next."
        :breadcrumbs="['For Suppliers' => route('suppliers'), 'How we work' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.flow label="How we work with manufacturers" :steps="array_column($steps, 0)" />
            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($steps as [$title, $text])
                    <li class="card">
                        <span class="font-heading text-3xl font-black text-orange-text">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="mt-2 text-xl">{{ $title }}</h2>
                        <p class="mt-2 text-base text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <x-page.cta-band title="Ready to start?" text="Share your company particulars, with your catalogue and documents." label="Become a Supply Partner" route="suppliers.apply" />
</x-layouts.site>
