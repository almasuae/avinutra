{{-- Home (Design Brief §6 layout; wording from Content Blueprint v3 §7.1 with the brief's fixes). --}}
@php
    use App\Support\SiteLinks;

    $features = [
        ['icon' => 'heroicon-o-beaker', 'title' => 'Feed Nutrition', 'text' => 'Science-based formulation support'],
        ['icon' => 'heroicon-o-presentation-chart-line', 'title' => 'Consulting', 'text' => 'Practical guidance for profitable production'],
        ['icon' => 'heroicon-o-cube', 'title' => 'Ingredient Supply', 'text' => 'Global sourcing with documented quality'],
        ['icon' => 'heroicon-o-globe-asia-australia', 'title' => 'International Reach', 'text' => 'Connecting global producers with Pakistan and beyond'],
    ];
    $pillars = [
        ['icon' => 'heroicon-o-document-check', 'title' => 'Documented Quality', 'text' => 'Specifications and certificates you can verify', 'tone' => 'orange'],
        ['icon' => 'heroicon-o-globe-alt', 'title' => 'Global Sourcing', 'text' => 'Established international manufacturers, focused on Pakistan', 'tone' => 'green'],
        ['icon' => 'heroicon-o-academic-cap', 'title' => 'Technical First', 'text' => 'Recommendations made or reviewed by feed nutritionists', 'tone' => 'orange'],
    ];
    $why = [
        ['title' => 'Technical', 'text' => 'Recommendations are made or reviewed by feed nutrition professionals.'],
        ['title' => 'Practical', 'text' => 'Solutions that work in commercial feed production.'],
        ['title' => 'Objective', 'text' => 'Products are compared on nutritional value, quality, economics and supply reliability, and we disclose our commercial relationships.'],
        ['title' => 'Global', 'text' => 'We evaluate and source from established international manufacturers.'],
        ['title' => 'Transparent', 'text' => 'Specifications, documents and the contracting entity are always stated.'],
        ['title' => 'Responsive', 'text' => 'Fast technical and commercial replies.'],
    ];
    $services = SiteLinks::url('services');
    $contact = SiteLinks::url('contact');
    $suppliers = SiteLinks::url('suppliers');
    $methionine = SiteLinks::url('ingredients.methionine');
    $calculator = SiteLinks::url('tools.methionine-value');
@endphp
<x-layouts.site
    title="AviNutra — Poultry Nutrition Expertise. Global Feed Ingredient Supply."
    description="International feed nutrition, consulting and ingredient supply for efficient and profitable poultry production."
>
    {{-- 1. Hero --}}
    <section class="overflow-hidden bg-gradient-to-br from-white via-white to-surface">
        <div class="mx-auto grid max-w-[84rem] items-center gap-10 px-4 pt-10 pb-14 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:pt-16 lg:pb-20 xl:px-8">
            <div>
                <p class="eyebrow">Poultry Feed Experts · Nutrition Consultants · Feed Ingredient Suppliers</p>
                <h1 class="mt-4 text-[2.375rem] leading-[1.08] tracking-tight sm:text-5xl lg:text-[3.75rem]">
                    Poultry Nutrition Expertise. Global Feed Ingredient Supply.
                </h1>
                <p class="mt-6 max-w-xl text-lg text-ink sm:text-xl">
                    International feed nutrition, consulting and ingredient supply for efficient and profitable poultry production.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    @if ($services)
                        <a href="{{ $services }}" class="btn-primary">Our Services <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                    @endif
                    @if ($contact)
                        <a href="{{ $contact }}" class="btn-secondary">Contact Us</a>
                    @endif
                </div>
            </div>
            <x-brand.shapes />
        </div>
    </section>

    {{-- 2. Feature strip --}}
    <section aria-label="What we do" class="border-y border-line bg-white">
        <ul class="mx-auto grid max-w-[84rem] divide-y divide-line px-4 sm:px-6 md:grid-cols-2 md:divide-y-0 lg:grid-cols-4 lg:divide-x xl:px-8">
            @foreach ($features as $feature)
                <li class="flex items-start gap-4 py-6 md:px-6 lg:first:pl-0">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                        <x-dynamic-component :component="$feature['icon']" class="size-7" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block font-heading text-lg font-black text-green-900">{{ $feature['title'] }}</span>
                        <span class="mt-1 block text-base text-muted">{{ $feature['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 3. Audience split --}}
    @if ($services || $suppliers)
        <section class="bg-surface">
            <div class="mx-auto grid max-w-[84rem] gap-6 px-4 py-14 sm:px-6 md:grid-cols-2 xl:px-8">
                @foreach ([[$services, 'For feed mills', 'I run or supply a feed mill', 'Formulation, ingredient evaluation, feed economics and reliable supply.'], [$suppliers, 'For manufacturers', 'I manufacture feed ingredients', 'Technical market development, qualified distribution and customer support.']] as [$href, $eyebrow, $title, $text])
                    @if ($href)
                        <a href="{{ $href }}" class="card group flex items-center justify-between gap-6 transition hover:-translate-y-0.5 hover:border-green-700">
                            <span>
                                <span class="eyebrow block">{{ $eyebrow }}</span>
                                <span class="mt-2 block font-heading text-2xl font-black text-green-900">{{ $title }}</span>
                                <span class="mt-2 block text-base text-muted">{{ $text }}</span>
                            </span>
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-cta text-white transition group-hover:bg-orange-text">
                                <x-heroicon-m-arrow-right class="size-6" aria-hidden="true" />
                            </span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    {{-- 4 + 5. Purpose block and value pillars --}}
    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.15fr_1fr] xl:px-8">
            <div class="relative">
                {{-- Brand shapes instead of a photo until images are approved (Design Brief §4). --}}
                <div class="relative hidden h-80 overflow-hidden rounded-[2rem] bg-gradient-to-br from-green-700 to-green-900 md:block" aria-hidden="true">
                    <svg viewBox="0 0 400 300" class="absolute inset-0 size-full" role="presentation">
                        <circle cx="90" cy="230" r="120" fill="#6ba727" opacity="0.35" />
                        <path d="M40 280 C 40 170, 120 90, 250 70 C 240 200, 170 280, 40 280 Z" fill="#6ba727" opacity="0.7" />
                        <circle cx="300" cy="80" r="46" fill="#fd9302" opacity="0.9" />
                        <circle cx="300" cy="80" r="64" fill="none" stroke="#fc7804" stroke-opacity="0.5" stroke-width="2" />
                    </svg>
                </div>
                <div class="card relative md:-mt-40 md:ml-16 md:mr-[-2rem]">
                    <p class="eyebrow">Our purpose</p>
                    <h2 class="mt-2 text-3xl leading-tight">Better Nutrition. Stronger Poultry. Better Economics.</h2>
                    <span class="accent-bar mt-4"></span>
                    <p class="mt-5 text-ink">
                        At AviNutra, we combine technical nutrition expertise, reliable ingredient sourcing and practical consulting to help feed manufacturers make better nutritional and commercial decisions.
                    </p>
                </div>
            </div>
            <ul class="grid gap-8 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                @foreach ($pillars as $pillar)
                    <li class="text-center">
                        <span @class(['mx-auto flex size-16 items-center justify-center rounded-full border-2', 'border-orange-500 text-orange-text' => $pillar['tone'] === 'orange', 'border-green-700 text-green-700' => $pillar['tone'] === 'green'])>
                            <x-dynamic-component :component="$pillar['icon']" class="size-8" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 text-xl">{{ $pillar['title'] }}</h3>
                        <p class="mt-2 text-base text-muted">{{ $pillar['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 6a. The problem we solve --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <div class="max-w-3xl">
                <x-page.heading eyebrow="The problem we solve" title="Feed Mills Need More Than a Supplier." />
                <p class="mt-6 text-lg">
                    Feed ingredient procurement is no longer a matter of finding the lowest quoted price. Feed manufacturers need consistent quality, reliable supply, technical confidence, formulation support and predictable economics. We bring these together through nutrition expertise, international sourcing and practical customer support.
                </p>
            </div>
            <x-page.flow class="mt-10" label="From nutrition to performance" :steps="['Nutrition', 'Product', 'Economics', 'Supply', 'Performance']" />
        </div>
    </section>

    {{-- 6b. Why AviNutra --}}
    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="Why AviNutra" title="Technical knowledge before commercial recommendation" />
            <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($why as $item)
                    <li class="card">
                        <h3 class="text-xl">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-base text-muted">{{ $item['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 6c. Precision Amino Acid Nutrition --}}
    <section class="bg-surface">
        <div class="mx-auto grid max-w-[84rem] items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.3fr_1fr] xl:px-8">
            <div>
                <x-page.heading eyebrow="Featured" title="Precision Amino Acid Nutrition" />
                <p class="mt-6 text-lg">
                    Amino acids are among the most economically important inputs in modern poultry feed. Their selection, specification and inclusion materially influence feed cost, nutrient efficiency and bird performance. We compare amino-acid products not by price per tonne, but by effective nutritional contribution, quality consistency and total cost of use.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    @if ($methionine)
                        <a href="{{ $methionine }}" class="btn-primary">Explore Methionine <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                    @endif
                    @if ($calculator)
                        <a href="{{ $calculator }}" class="btn-secondary">Try the Methionine Value Calculator</a>
                    @endif
                </div>
            </div>
            <div class="card">
                <p class="eyebrow">Compare on value, not price</p>
                <p class="mt-3 font-heading text-2xl font-black text-green-900">Cost per kg of effective methionine</p>
                <p class="mt-3 font-mono text-base text-ink">= price per kg ÷ value factor</p>
                <p class="mt-4 text-base text-muted">The value factor is the kg of DL-Methionine 99% replaced by 1 kg of the product. Comparing sources on this basis shows the real difference between them.</p>
            </div>
        </div>
    </section>

    {{-- 6d. Latest Insights (hidden while no article is published) --}}
    @if ($articles->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <x-page.heading eyebrow="Knowledge Centre" title="Latest Insights" />
                    <a href="{{ route('knowledge') }}" class="font-semibold text-green-700 hover:underline">All insights →</a>
                </div>
                <ul class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($articles as $article)
                        <li class="card flex flex-col">
                            <p class="eyebrow">{{ $article->category->getLabel() }}</p>
                            <h3 class="mt-2 text-xl leading-snug"><a href="{{ route('knowledge.show', $article->slug) }}" class="hover:text-green-700 hover:underline">{{ $article->title }}</a></h3>
                            @if ($article->summary)
                                <p class="mt-3 flex-1 text-base text-muted">{{ $article->summary }}</p>
                            @endif
                            <p class="mt-4 text-sm text-muted">{{ $article->authorName() }} · {{ $article->published_at?->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- 6e. Closing call to action --}}
    <x-page.cta-band />
</x-layouts.site>
