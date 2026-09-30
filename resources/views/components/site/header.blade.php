@props(['preview' => false])
{{--
    Site header (Design Brief §5): logo left, navigation centre-right, a dark-green
    pill CTA. Sticky, with a soft shadow once the page scrolls; its edge line and
    shadow follow the hero style (site.hero_style, see .site-header in app.css). Below 1280px the
    navigation moves into a full-height drawer with the CTA pinned at the bottom.
    Links appear only when their page exists ($preview shows all, for design review).
--}}
@php
    $link = fn (array $item): ?string => \Illuminate\Support\Facades\Route::has($item['route'])
        ? route($item['route'])
        : ($preview ? '#' : null);
    $items = collect(config('site.navigation'))
        ->map(fn (array $item): array => $item + ['href' => $link($item), 'active' => request()->routeIs($item['route'], $item['route'].'.*')])
        ->filter(fn (array $item): bool => $item['href'] !== null);
    $cta = config('site.cta');
    $ctaHref = $link($cta);
@endphp
<header data-site-header class="site-header sticky top-0 z-40 bg-white transition-shadow duration-200">
    <div class="mx-auto flex h-[4.5rem] max-w-[84rem] items-center gap-6 px-4 sm:px-6 xl:h-[5.5rem] xl:px-8">
        <a href="{{ url('/') }}" class="shrink-0">
            <x-brand.logo variant="compact" class="h-9 w-auto xl:h-11" />
        </a>

        @if ($items->isNotEmpty())
            <nav aria-label="Main" class="ml-auto hidden xl:block">
                <ul class="flex items-center gap-7">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item['href'] }}"
                               @if ($item['active']) aria-current="page" @endif
                               class="relative block py-2 text-[0.95rem] font-medium text-ink transition-colors hover:text-green-700 aria-[current=page]:font-semibold aria-[current=page]:text-green-800 after:absolute after:inset-x-0 after:-bottom-0.5 after:h-[3px] after:rounded-full after:bg-green-700 after:opacity-0 aria-[current=page]:after:opacity-100">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div @class(['flex items-center gap-4', 'ml-auto' => $items->isEmpty(), 'max-xl:ml-auto'])>
            @if (config('site.search'))
                <a href="{{ route('search') }}" class="rounded-full p-2 text-green-800 hover:bg-surface" aria-label="Search">
                    <x-heroicon-o-magnifying-glass class="size-5" aria-hidden="true" />
                </a>
            @endif

            @if ($ctaHref !== null)
                <a href="{{ $ctaHref }}" class="btn-cta hidden xl:inline-flex">
                    {{ $cta['label'] }}
                    <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" />
                </a>
            @endif

            @if ($items->isNotEmpty() || $ctaHref !== null)
                <button type="button" data-menu-open aria-controls="site-menu" aria-expanded="false"
                        class="-mr-2 inline-flex items-center gap-2 rounded-full p-2 font-semibold text-green-800 hover:bg-surface xl:hidden">
                    <x-heroicon-o-bars-3 class="size-7" aria-hidden="true" />
                    <span class="sr-only">Menu</span>
                </button>
            @endif
        </div>
    </div>

    @if ($items->isNotEmpty() || $ctaHref !== null)
        <dialog id="site-menu" aria-label="Menu"
                class="m-0 ml-auto h-dvh max-h-none w-full max-w-md bg-white p-0 backdrop:bg-green-900/40 open:flex open:flex-col">
            <div class="flex h-[4.5rem] shrink-0 items-center justify-between border-b border-line px-4 sm:px-6">
                <x-brand.logo variant="compact" class="h-9 w-auto" />
                <button type="button" data-menu-close class="-mr-2 rounded-full p-2 text-green-800 hover:bg-surface">
                    <x-heroicon-o-x-mark class="size-7" aria-hidden="true" />
                    <span class="sr-only">Close menu</span>
                </button>
            </div>
            <nav aria-label="Main" class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                <ul class="divide-y divide-line">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item['href'] }}"
                               @if ($item['active']) aria-current="page" @endif
                               class="flex items-center justify-between py-4 font-heading text-xl font-black text-green-900 aria-[current=page]:text-green-700">
                                {{ $item['label'] }}
                                <x-heroicon-m-chevron-right class="size-5 text-orange-500" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            @if ($ctaHref !== null)
                <div class="shrink-0 border-t border-line p-4 sm:p-6">
                    <a href="{{ $ctaHref }}" class="btn-cta w-full">
                        {{ $cta['label'] }}
                        <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" />
                    </a>
                </div>
            @endif
        </dialog>
    @endif
</header>
