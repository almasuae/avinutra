{{--
    Stand-alone error page (500, 503): no database and no Vite assets, because it is
    shown when something is broken, and `artisan down` pre-renders the 503 page before
    a deploy replaces the built assets. Styles are inline; the logo is a static file.
--}}
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f7f8f6; color: #1c2226; }
        main { max-width: 34rem; padding: 2rem 1.25rem; text-align: center; }
        img { height: 44px; width: auto; }
        h1 { margin: 1.75rem 0 0.75rem; font-size: 1.75rem; line-height: 1.2; color: #013b32; }
        p { margin: 0; font-size: 1.0625rem; line-height: 1.6; color: #5b6770; }
        .bar { display: block; width: 40px; height: 3px; margin: 1rem auto 0; background: #fc7804; }
    </style>
</head>
<body>
    <main>
        <img src="/brand/logo-compact@2x.png" width="225" height="44" alt="{{ config('app.name') }}">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <span class="bar"></span>
    </main>
</body>
</html>
