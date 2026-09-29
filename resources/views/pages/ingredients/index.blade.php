{{-- Ingredient directory (v3 §7.4): categories, and products from CRM › Products marked "publish on website". --}}
<x-layouts.site
    title="Feed Ingredients — AviNutra"
    description="Amino acids, enzymes, vitamins and minerals, mycotoxin management, gut health and specialty feed additives for poultry feed manufacturers."
>
    <x-page.hero
        eyebrow="Ingredients"
        title="Feed Ingredients"
        lead="Amino acids, enzymes and specialty additives, compared on specification, nutritional contribution and cost of use — not price per tonne alone."
        :breadcrumbs="['Ingredients' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="Categories" title="Browse by category" />
            <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('ingredients.category', $category['slug']) }}" class="card group flex h-full flex-col transition hover:-translate-y-0.5 hover:border-green-700">
                            <span class="flex size-14 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                                <x-dynamic-component :component="$category['icon']" class="size-7" aria-hidden="true" />
                            </span>
                            <span class="mt-5 font-heading text-xl font-black text-green-900">{{ $category['title'] }}</span>
                            <span class="mt-2 flex-1 text-base text-muted">{{ $category['summary'] }}</span>
                            <span class="mt-5 font-semibold text-green-700 group-hover:underline">View category →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto grid max-w-[84rem] items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.3fr_1fr] xl:px-8">
            <div>
                <x-page.heading eyebrow="Flagship guide" title="Methionine: sources, specification and value" />
                <p class="mt-6 text-lg">DL-Methionine, L-Methionine and methionine hydroxy analogue compared on a neutral basis, with how to calculate the cost per kg of effective methionine and a documentation checklist.</p>
                <a href="{{ route('ingredients.methionine') }}" class="btn-primary mt-8">Explore Methionine <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
            </div>
            <div class="card">
                <p class="eyebrow">How products are shown</p>
                <p class="mt-3 text-base">Each product states whether it is <strong>available</strong>, <strong>sourced on request</strong> or described for <strong>information</strong> only. Documents such as the TDS or COA are sent on request.</p>
            </div>
        </div>
    </section>

    @if ($hasProducts)
        <section class="bg-white" id="products">
            <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
                <x-page.heading eyebrow="Directory" title="Products" />
                <form method="get" action="{{ route('ingredients') }}#products" class="mt-8 grid gap-4 rounded-(--radius-card) bg-surface p-5 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="filter-category" class="block text-sm font-semibold text-green-900">Category</label>
                        <select id="filter-category" name="category" class="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2.5">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category['slug'] }}" @selected(($selected['category'] ?? '') === $category['slug'])>{{ $category['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ($filters as $key => $filter)
                        <div>
                            <label for="filter-{{ $key }}" class="block text-sm font-semibold text-green-900">{{ $filter['label'] }}</label>
                            <select id="filter-{{ $key }}" name="{{ $key }}" class="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2.5">
                                <option value="">All</option>
                                @foreach ($filter['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($selected[$key] ?? '') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="flex items-end">
                        <button type="submit" class="btn-cta w-full">Filter</button>
                    </div>
                </form>

                @if ($products->isEmpty())
                    <p class="mt-8 text-lg text-muted">No products match these filters. <a href="{{ route('ingredients') }}#products" class="font-semibold text-green-700 underline">Clear the filters</a>.</p>
                @else
                    <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($products as $product)
                            @include('pages.ingredients.partials.product-card', ['product' => $product])
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    <x-page.cta-band title="Looking for a specific product?" text="If it is not listed, we can look for reliable international sources." label="Request Sourcing Support" route="services.request-sourcing" />
</x-layouts.site>
