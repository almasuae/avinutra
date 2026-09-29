@props(['variant' => 'compact', 'alt' => null])
{{--
    AviNutra logo from public/brand (built by `npm run brand:build`; sizes in config/brand.php).
    Always the 2x file, with the 1x size as width/height so the layout never shifts.
--}}
@php
    $name = 'logo-'.$variant;
    $size = config("brand.variants.{$name}", ['width' => 0, 'height' => 0]);
    $version = config('brand.version');
    $alt ??= config('app.name');
@endphp
<picture>
    <source type="image/webp" srcset="{{ asset("brand/{$name}@2x.webp") }}?v={{ $version }}">
    <img
        src="{{ asset("brand/{$name}@2x.png") }}?v={{ $version }}"
        width="{{ $size['width'] }}"
        height="{{ $size['height'] }}"
        alt="{{ $alt }}"
        {{ $attributes }}
    >
</picture>
