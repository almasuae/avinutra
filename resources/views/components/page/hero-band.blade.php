{{--
    The top section of every public page (owner's decision, 1 Oct 2026). Its look —
    background, and the header line and shadow above it — comes from the one hero-style
    setting (config site.hero_style → <html data-hero>, CSS .site-hero in app.css).
    Inner pages use it through <x-page.hero>; the home page wraps its own layout in it.
--}}
<section data-hero-band {{ $attributes->class(['site-hero']) }}>
    {{ $slot }}
</section>
