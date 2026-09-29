{{-- Ingredient category page (v3 §7.4): educational text, then the products the owner has published. --}}
<x-layouts.site :title="$category['title'].' — Feed Ingredients — AviNutra'" :description="$category['summary']">
    <x-page.hero
        eyebrow="Ingredients"
        :title="$category['title']"
        :lead="$category['summary']"
        :breadcrumbs="['Ingredients' => route('ingredients'), $category['title'] => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.6fr_1fr] xl:px-8">
            <div>
                <x-page.heading :title="'About '.mb_strtolower($category['title'])" />
                <p class="mt-6 text-lg">{{ $category['text'] }}</p>

                @if ($products->isNotEmpty())
                    <h2 class="mt-12 text-2xl">Products</h2>
                    <ul class="mt-6 grid gap-6 sm:grid-cols-2">
                        @foreach ($products as $product)
                            @include('pages.ingredients.partials.product-card', ['product' => $product])
                        @endforeach
                    </ul>
                @endif
            </div>
            <aside class="space-y-6 self-start">
                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">Products in this category include</h2>
                    <ul class="mt-4 space-y-2 text-base">
                        @foreach ($category['examples'] as $example)
                            <li class="flex gap-2"><x-heroicon-m-check class="mt-1 size-5 shrink-0 text-green-700" aria-hidden="true" />{{ $example }}</li>
                        @endforeach
                    </ul>
                    @if ($category['key'] === 'amino_acids')
                        <a href="{{ route('ingredients.methionine') }}" class="btn-secondary mt-6">Methionine guide</a>
                    @endif
                </div>
                <div class="rounded-(--radius-card) bg-surface p-7 text-base">
                    <h2 class="text-xl">Need a product?</h2>
                    <p class="mt-2 text-muted">Tell us the specification and quantity, and we will look for reliable international sources.</p>
                    <a href="{{ route('services.request-sourcing') }}" class="mt-4 inline-block font-semibold text-green-700 hover:underline">Request Sourcing Support →</a>
                </div>
            </aside>
        </div>
    </section>

    <x-page.cta-band title="Compare products on value" text="Our nutritionists can compare products for your formulation, on cost of use rather than price per tonne." label="Ask a Nutritionist" />
</x-layouts.site>
