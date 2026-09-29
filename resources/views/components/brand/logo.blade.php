@props(['variant' => 'compact', 'alt' => null])
{{--
    AviNutra logo from public/brand (built by `npm run brand:build`; sizes in config/brand.php).
    1x and 2x files by pixel density, with the 1x size as width/height so the layout never shifts.
--}}
@php
    $name = 'logo-'.$variant;
    $size = config("brand.variants.{$name}", ['width' => 0, 'height' => 0]);
    $version = config('brand.version');
    $alt ??= config('app.name');
    $srcset = fn (string $ext): string => (is_file(public_path("brand/{$name}.{$ext}")) ? asset("brand/{$name}.{$ext}")."?v={$version} 1x, " : '')
        .asset("brand/{$name}@2x.{$ext}")."?v={$version} 2x";
@endphp
<picture>
    <source type="image/webp" srcset="{{ $srcset('webp') }}">
    <img
        src="{{ asset("brand/{$name}@2x.png") }}?v={{ $version }}"
        srcset="{{ $srcset('png') }}"
        width="{{ $size['width'] }}"
        height="{{ $size['height'] }}"
        alt="{{ $alt }}"
        {{ $attributes }}
    >
</picture>
