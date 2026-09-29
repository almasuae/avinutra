@props(['title', 'eyebrow' => null, 'level' => 2, 'centered' => false, 'dark' => false])
{{-- Section heading: eyebrow label, title, short orange accent bar (Design Brief §3). --}}
<div {{ $attributes->class(['text-center' => $centered]) }}>
    @if ($eyebrow)
        <p @class(['eyebrow', 'text-orange-400!' => $dark])>{{ $eyebrow }}</p>
    @endif
    <h{{ $level }} @class(['mt-2 text-3xl leading-tight tracking-tight sm:text-[2.25rem]', 'text-white!' => $dark])>{{ $title }}</h{{ $level }}>
    <span @class(['accent-bar mt-4', 'mx-auto' => $centered])></span>
</div>
