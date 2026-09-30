@props(['title', 'eyebrow' => null, 'lead' => null, 'breadcrumbs' => []])
{{--
    Hero band for inner pages (Design Brief §6 "Other pages"): breadcrumb, title,
    lead and a brand-shape strip. The page's single H1.
--}}
<x-page.hero-band class="relative overflow-hidden border-b border-line">
    <svg viewBox="0 0 400 300" class="pointer-events-none absolute -top-24 -right-24 hidden size-[26rem] opacity-90 md:block" aria-hidden="true" role="presentation">
        <defs>
            <linearGradient id="hero-leaf" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#6ba727" />
                <stop offset="1" stop-color="#025e3d" />
            </linearGradient>
            <linearGradient id="hero-orange" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#fd9302" />
                <stop offset="1" stop-color="#fc6b01" />
            </linearGradient>
        </defs>
        <circle cx="250" cy="150" r="140" fill="#f7f8f6" stroke="#e3e7e4" stroke-width="2" />
        <path d="M150 260 C 150 160, 230 90, 350 80 C 340 200, 270 262, 150 260 Z" fill="url(#hero-leaf)" opacity="0.9" />
        <circle cx="120" cy="120" r="22" fill="url(#hero-orange)" />
    </svg>
    <div class="relative mx-auto max-w-[84rem] px-4 py-12 sm:px-6 md:py-16 xl:px-8">
        @if ($breadcrumbs !== [])
            <nav aria-label="Breadcrumb" class="mb-5 text-sm text-muted">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <li><a href="{{ url('/') }}" class="hover:text-green-700 hover:underline">Home</a></li>
                    @foreach ($breadcrumbs as $label => $href)
                        <li class="flex items-center gap-2">
                            <x-heroicon-m-chevron-right class="size-4 text-orange-500" aria-hidden="true" />
                            @if ($href && ! $loop->last)
                                <a href="{{ $href }}" class="hover:text-green-700 hover:underline">{{ $label }}</a>
                            @else
                                <span @if ($loop->last) aria-current="page" @endif class="text-ink">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <div class="max-w-3xl md:max-w-[60%]">
            @if ($eyebrow)
                <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-2 text-[2.25rem] leading-[1.1] tracking-tight sm:text-5xl">{{ $title }}</h1>
            @if ($lead)
                <p class="mt-5 text-lg text-ink sm:text-xl">{{ $lead }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</x-page.hero-band>
@if ($breadcrumbs !== [])
    @push('head')
        <x-seo.json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect(['Home' => url('/')] + $breadcrumbs)
                ->map(fn (?string $href, string $label): array => ['name' => $label, 'item' => $href ?? url()->current()])
                ->values()
                ->map(fn (array $crumb, int $index): array => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['name'], 'item' => $crumb['item']])
                ->all(),
        ]" />
    @endpush
@endif
