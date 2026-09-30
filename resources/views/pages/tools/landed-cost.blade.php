{{-- Tool 2 page: universal landed cost (owner's specification of 29 Sep 2026). --}}
<x-layouts.site
    :print-notice="true"
    title="Landed Cost Calculator — AviNutra"
    description="Calculate the landed cost of an imported feed additive per kg, per tonne and per shipment, in any currency, with duties, taxes and local charges."
>
    <x-page.hero
        eyebrow="Tools"
        title="Landed Cost Calculator"
        lead="From the purchase price to the cost in your warehouse: freight, insurance, duties, taxes, local charges and financing, in any currency."
        :breadcrumbs="['Tools' => route('tools'), 'Landed Cost Calculator' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-12 sm:px-6 xl:px-8">
            <livewire:landed-cost />
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <x-tools.technical-notice />
        </div>
    </section>
</x-layouts.site>
