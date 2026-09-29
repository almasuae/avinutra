{{--
    Hero collage made of brand shapes only (Design Brief §4 item 5: no photos until
    approved images exist, and never grey boxes). Decorative: hidden from assistive tech.
    On small screens only the "Global ingredients" panel is shown.
--}}
<div {{ $attributes->class(['relative']) }} aria-hidden="true">
    <div class="relative hidden aspect-[5/4] w-full lg:block">
        <svg viewBox="0 0 500 400" class="absolute inset-0 size-full" role="presentation">
            <defs>
                <linearGradient id="shape-orange" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#fd9302" />
                    <stop offset="1" stop-color="#fc6b01" />
                </linearGradient>
                <linearGradient id="shape-leaf" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#6ba727" />
                    <stop offset="1" stop-color="#025e3d" />
                </linearGradient>
                <linearGradient id="shape-green" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#025e3d" />
                    <stop offset="1" stop-color="#013b32" />
                </linearGradient>
            </defs>
            {{-- Large soft circle --}}
            <circle cx="170" cy="210" r="165" fill="#f7f8f6" />
            <circle cx="170" cy="210" r="165" fill="none" stroke="#e3e7e4" stroke-width="2" />
            {{-- Leaf --}}
            <path d="M60 330 C 60 190, 170 90, 330 70 C 320 230, 220 330, 60 330 Z" fill="url(#shape-leaf)" />
            <path d="M75 318 C 150 240, 230 160, 318 84" fill="none" stroke="#ffffff" stroke-opacity="0.55" stroke-width="3" stroke-linecap="round" />
            {{-- Orange circles --}}
            <circle cx="300" cy="300" r="62" fill="url(#shape-orange)" />
            <circle cx="300" cy="300" r="78" fill="none" stroke="#fc7804" stroke-opacity="0.35" stroke-width="2" />
            <circle cx="105" cy="95" r="26" fill="url(#shape-orange)" />
            {{-- Dotted ring --}}
            <circle cx="170" cy="210" r="190" fill="none" stroke="#6ba727" stroke-opacity="0.35" stroke-width="2" stroke-dasharray="2 10" stroke-linecap="round" />
        </svg>
        <div class="absolute right-0 bottom-6 w-[46%]">
            @include('components.brand.global-panel')
        </div>
    </div>
    <div class="lg:hidden">
        @include('components.brand.global-panel')
    </div>
</div>
