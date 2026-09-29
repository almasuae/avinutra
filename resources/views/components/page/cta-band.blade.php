@props([
    'title' => 'Have a formulation or sourcing question?',
    'text' => 'Our nutrition team answers technical and commercial questions from feed manufacturers.',
    'label' => 'Ask a Nutritionist',
    'route' => 'ask-a-nutritionist',
    'parameters' => [],
])
{{-- Closing call-to-action band on dark green (Design Brief §6). WhatsApp appears once a number is set. --}}
@php
    $href = \App\Support\SiteLinks::url($route, $parameters);
    $whatsapp = \App\Support\SiteLinks::whatsapp(app(\App\Settings\SiteSettings::class)->whatsapp_sales);
@endphp
<section class="bg-green-800 text-white">
    <div class="mx-auto flex max-w-[84rem] flex-col gap-8 px-4 py-14 sm:px-6 md:flex-row md:items-center md:justify-between xl:px-8">
        <div class="max-w-2xl">
            <h2 class="text-3xl leading-tight text-white! sm:text-4xl">{{ $title }}</h2>
            <p class="mt-3 text-lg text-on-dark-muted">{{ $text }}</p>
        </div>
        <div class="flex flex-wrap gap-4">
            @if ($href)
                <a href="{{ $href }}" class="btn-primary">{{ $label }} <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
            @endif
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" class="btn border-2 border-white text-white hover:bg-white hover:text-green-800">WhatsApp us</a>
            @endif
        </div>
    </div>
</section>
