<x-layouts.site title="Page not found — AviNutra">
    <section class="site-hero">
        <div class="mx-auto max-w-3xl px-4 py-24 text-center sm:px-6">
            <p class="eyebrow">Error 404</p>
            <h1 class="mt-3 text-4xl sm:text-5xl">This page could not be found</h1>
            <span class="accent-bar mx-auto mt-5"></span>
            <p class="mt-6 text-lg text-muted">The page may have moved, or the link may be mistyped.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ url('/') }}" class="btn-primary">Go to the home page</a>
                @if ($contact = \App\Support\SiteLinks::url('contact'))
                    <a href="{{ $contact }}" class="btn-secondary">Contact us</a>
                @endif
            </div>
        </div>
    </section>
</x-layouts.site>
