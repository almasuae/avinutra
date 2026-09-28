<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
    <link rel="canonical" href="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

    <header class="border-b border-line">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }} home"><x-wordmark /></a>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-muted sm:px-6">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </div>
    </footer>
</body>
</html>
