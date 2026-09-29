@props(['title' => null, 'description' => null, 'preview' => false])
@php
    $brandVersion = config('brand.version');
    // CRM › Website › Page SEO overrides the page's own title and description.
    $seo = \App\Models\PageSeo::forRoute(request()->route()?->getName());
    $title = filled($seo?->title) ? $seo->title : $title;
    $description = filled($seo?->description) ? $seo->description : $description;
    $whatsapp = \App\Support\SiteLinks::whatsapp(app(\App\Settings\SiteSettings::class)->whatsapp_sales);
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $brandVersion }}" sizes="32x32">
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon-32.png') }}?v={{ $brandVersion }}" sizes="32x32">
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon-16.png') }}?v={{ $brandVersion }}" sizes="16x16">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}?v={{ $brandVersion }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#034c33">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:image" content="{{ asset('brand/og-image.png') }}?v={{ $brandVersion }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

    <x-site.header :preview="$preview" />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer :preview="$preview" />

    @if ($whatsapp)
        {{-- Sticky WhatsApp button on phones, once a number is set in Site settings (v3 §7.10). --}}
        <a href="{{ $whatsapp }}" class="btn-cta fixed right-4 bottom-4 z-30 shadow-lg md:hidden">WhatsApp us</a>
    @endif
</body>
</html>
