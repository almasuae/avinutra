{{--
    Local design review page (APP_ENV=local only; never registered in production).
    Shows the heading-font options, the header and both footer options, the logo
    on light and dark backgrounds, and the colour tokens with their contrast.
    Navigation links point at "#" here because the pages are built in Phase 7.
--}}
@php
    use App\Support\BrandPalette;

    $fonts = [
        'a' => ['label' => 'Option A — Nunito Sans ExtraBold (800)', 'class' => 'font-heading-a font-extrabold'],
        'b' => ['label' => 'Option B — Lato Black (900)', 'class' => 'font-heading-b font-black'],
    ];
@endphp
<x-layouts.site title="Design review — AviNutra (local only)" :preview="true">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-[84rem] px-4 py-4 text-sm text-muted sm:px-6 xl:px-8">
            Design review · local only · not part of the public site. Navigation links are placeholders until Phase 7.
        </div>
    </div>

    {{-- 1. Heading-font comparison on the hero --}}
    @foreach ($fonts as $key => $font)
        <section data-shot="hero-font-{{ $key }}" class="overflow-hidden border-b border-line bg-gradient-to-br from-white via-white to-surface">
            <div class="mx-auto max-w-[84rem] px-4 pt-6 sm:px-6 xl:px-8">
                <p class="inline-block rounded-full bg-green-800 px-3 py-1 text-xs font-semibold tracking-wide text-white uppercase">{{ $font['label'] }}</p>
            </div>
            <div class="mx-auto grid max-w-[84rem] items-center gap-10 px-4 pt-8 pb-14 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:pt-12 lg:pb-20 xl:px-8">
                <div>
                    <h2 class="{{ $font['class'] }} text-[2.375rem] leading-[1.08] tracking-tight text-green-900 sm:text-5xl lg:text-[3.75rem]">
                        Poultry Nutrition Expertise. Global Feed Ingredient Supply.
                    </h2>
                    <p class="mt-6 max-w-xl text-lg text-ink sm:text-xl">
                        International feed nutrition, consulting and ingredient supply for efficient and profitable poultry production.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="#" class="btn-primary">Our Services <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                        <a href="#" class="btn-secondary">Contact Us</a>
                    </div>
                </div>
                <x-brand.shapes />
            </div>
        </section>
    @endforeach

    {{-- 2. Footer options --}}
    <section class="border-b border-line">
        <div class="mx-auto max-w-[84rem] px-4 py-8 sm:px-6 xl:px-8">
            <p class="eyebrow">Footer option A</p>
            <h2 class="mt-2 text-2xl font-extrabold">Light surface footer with the full logo</h2>
        </div>
        <x-site.footer variant="light" :preview="true" data-shot="footer-a" />
        <div class="mx-auto max-w-[84rem] px-4 py-8 sm:px-6 xl:px-8">
            <p class="eyebrow">Footer option B</p>
            <h2 class="mt-2 text-2xl font-extrabold">Dark-green footer, logo on a white rounded panel</h2>
        </div>
        <x-site.footer variant="dark" :preview="true" data-shot="footer-b" />
    </section>

    {{-- 3. The logo on light and dark backgrounds --}}
    <section data-shot="logo-backgrounds" class="border-b border-line">
        <div class="mx-auto max-w-[84rem] px-4 py-12 sm:px-6 xl:px-8">
            <p class="eyebrow">Logo</p>
            <h2 class="mt-2 text-2xl font-extrabold">Cleaned logo on light and dark backgrounds</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="flex min-h-48 items-center justify-center rounded-(--radius-card) border border-line bg-white p-8">
                    <x-brand.logo variant="full" class="h-auto w-full max-w-md" />
                </div>
                <div class="flex min-h-48 items-center justify-center rounded-(--radius-card) bg-surface p-8">
                    <x-brand.logo variant="full" class="h-auto w-full max-w-md" />
                </div>
                <div class="flex min-h-48 items-center justify-center rounded-(--radius-card) bg-green-800 p-8">
                    <span class="rounded-2xl bg-white px-6 py-5"><x-brand.logo variant="full" class="h-auto w-full max-w-sm" /></span>
                </div>
                <div class="flex min-h-48 items-center justify-center rounded-(--radius-card) bg-green-800 p-8">
                    <x-brand.logo variant="full" class="h-auto w-full max-w-md" alt="AviNutra (directly on dark green, for comparison)" />
                </div>
            </div>
            <div class="mt-6 flex flex-wrap items-end gap-8 rounded-(--radius-card) border border-line p-8">
                <x-brand.logo variant="compact" class="h-11 w-auto" />
                <x-brand.logo variant="mark" class="size-16" />
                <img src="{{ asset('brand/apple-touch-icon.png') }}" width="60" height="60" alt="Home-screen icon" class="rounded-xl border border-line">
                <img src="{{ asset('brand/favicon-32.png') }}" width="32" height="32" alt="Favicon, 32px">
                <img src="{{ asset('brand/favicon-16.png') }}" width="16" height="16" alt="Favicon, 16px">
            </div>
        </div>
    </section>

    {{-- 4. Colour tokens and contrast --}}
    <section data-shot="tokens" class="border-b border-line">
        <div class="mx-auto max-w-[84rem] px-4 py-12 sm:px-6 xl:px-8">
            <p class="eyebrow">Design tokens</p>
            <h2 class="mt-2 text-2xl font-extrabold">Colours and contrast (WCAG AA)</h2>
            <ul class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach (BrandPalette::TOKENS as $name => $hex)
                    <li class="overflow-hidden rounded-xl border border-line">
                        <span class="block h-16" style="background: {{ $hex }}"></span>
                        <span class="block px-3 py-2 text-sm"><strong>{{ $name }}</strong><br><span class="text-muted">{{ strtoupper($hex) }}</span></span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="text-muted"><tr><th class="py-2">Sample</th><th>Text / background</th><th>Ratio</th><th>Needed</th><th>Used for</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach (BrandPalette::PAIRS as [$fg, $bg, $min, $use])
                            <tr>
                                <td class="py-2"><span class="inline-block rounded px-2 py-1 font-semibold" style="color: {{ BrandPalette::hex($fg) }}; background: {{ BrandPalette::hex($bg) }}">Aa</span></td>
                                <td>{{ $fg }} / {{ $bg }}</td>
                                <td class="font-semibold">{{ number_format(BrandPalette::contrast($fg, $bg), 2) }}:1</td>
                                <td>{{ number_format($min, 1) }}:1</td>
                                <td class="text-muted">{{ $use }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.site>
