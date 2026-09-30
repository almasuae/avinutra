{{-- Tool 1 page (v5 §E3). --}}
<x-layouts.site
    :print-notice="true"
    title="Methionine Value Calculator — AviNutra"
    description="Compare DL-Methionine, L-Methionine and MHA on cost per kg of effective methionine and cost per tonne of feed."
>
    <x-page.hero
        eyebrow="Tools"
        title="Methionine Value Calculator"
        lead="Compare 2 to 4 methionine sources on an equal basis: cost per kg of effective methionine, cost per tonne of feed, and the difference per month and per year."
        :breadcrumbs="['Tools' => route('tools'), 'Methionine Value Calculator' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-12 sm:px-6 xl:px-8">
            <livewire:methionine-value />
            <p class="mt-8 text-base">Background: <a href="{{ route('ingredients.methionine') }}" class="font-semibold text-green-700 underline">Methionine: sources, specification and value</a>.</p>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <x-tools.technical-notice />
        </div>
    </section>
</x-layouts.site>
