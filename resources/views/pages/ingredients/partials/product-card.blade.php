@php($categorySlug = \App\Content\IngredientCategories::slug((string) $product->category?->key))
<li>
    <a href="{{ route('ingredients.product', [$categorySlug, $product->slug]) }}" class="card group flex h-full flex-col transition hover:-translate-y-0.5 hover:border-green-700">
        <span class="eyebrow">{{ $product->category?->label }}</span>
        <span class="mt-2 font-heading text-xl font-black text-green-900">{{ $product->name }}</span>
        @if ($product->description)
            <span class="mt-2 flex-1 text-base text-muted">{{ \Illuminate\Support\Str::limit($product->description, 140) }}</span>
        @endif
        <span class="mt-4 inline-block self-start rounded-full bg-surface px-3 py-1 text-sm font-semibold text-green-900">{{ \App\Support\ProductPresentation::badge($product->availability) }}</span>
    </a>
</li>
