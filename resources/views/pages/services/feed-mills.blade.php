{{-- Solutions for Feed Manufacturers: the customer journey (v3 §7.3). --}}
@php
    $journey = [
        ['Requirement', 'You tell us what you need: a product, a specification, a formulation question or a cost target.'],
        ['Technical assessment', 'We review the requirement against your formulation, performance targets and current products.'],
        ['Product selection', 'We compare suitable products on specification, nutritional value, documentation and cost of use.'],
        ['Sample', 'A sample for evaluation, with its certificate of analysis.'],
        ['Trial', 'Where a change is significant, a trial with a clear protocol and agreed indicators.'],
        ['Quote', 'A quotation that states the product, specification, terms and the contracting entity.'],
        ['Supply', 'Shipment with complete documentation for each batch.'],
        ['Technical follow-up', 'We stay in touch after supply to review performance and answer questions.'],
    ];
@endphp
<x-layouts.site
    title="Solutions for Feed Manufacturers — AviNutra"
    description="How AviNutra works with feed mills: from requirement and technical assessment to supply and technical follow-up."
>
    <x-page.hero
        eyebrow="Nutrition Services"
        title="Solutions for Feed Manufacturers"
        lead="We work with feed mills from the first technical question to supply and follow-up — with a nutritionist involved at every step."
        :breadcrumbs="['Nutrition Services' => route('services'), 'Feed Manufacturers' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="The customer journey" title="From requirement to technical follow-up" />
            <x-page.flow class="mt-8" label="The customer journey" :steps="array_column($journey, 0)" />
            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($journey as [$title, $text])
                    <li class="card">
                        <span class="font-heading text-3xl font-black text-orange-text">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mt-2 text-xl">{{ $title }}</h3>
                        <p class="mt-2 text-base text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <x-page.cta-band title="Start with a technical question" label="Talk to a Nutritionist" />
</x-layouts.site>
