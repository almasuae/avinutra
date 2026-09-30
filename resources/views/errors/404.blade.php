<x-layouts.site title="Page not found — AviNutra">
    {{-- The shared page hero, like every other public page (hero style: site.hero_style). --}}
    <x-page.hero
        eyebrow="Error 404"
        title="This page could not be found"
        lead="The page may have moved, or the link may be mistyped."
    >
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ url('/') }}" class="btn-primary">Go to the home page</a>
            @if ($contact = \App\Support\SiteLinks::url('contact'))
                <a href="{{ $contact }}" class="btn-secondary">Contact us</a>
            @endif
        </div>
    </x-page.hero>
</x-layouts.site>
