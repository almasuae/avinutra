@props(['variant' => null, 'preview' => false])
{{--
    Site footer (Design Brief §6 item 7), in two styles for the owner to choose:
      light (A): surface background with the full logo;
      dark  (B): dark green with the full-colour logo on a white rounded panel.
    Four columns: About · Services · Resources · Contact. Status statement (from
    Site settings, never hard-coded) and legal links in the bottom bar.
--}}
@php
    $variant ??= config('site.footer', 'light');
    $dark = $variant === 'dark';
    $site = app(\App\Settings\SiteSettings::class);
    $link = fn (array $item): ?string => \Illuminate\Support\Facades\Route::has($item['route'])
        ? route($item['route'])
        : ($preview ? '#' : null);
    $resolve = fn (array $items) => collect($items)
        ->map(fn (array $item): array => $item + ['href' => $link($item)])
        ->filter(fn (array $item): bool => $item['href'] !== null);
    $columns = collect(config('site.footer_columns'))->map($resolve)->filter(fn ($items) => $items->isNotEmpty());
    $legal = $resolve(config('site.legal'));
    $mailboxes = collect([
        'General enquiries' => $site->emails['info'] ?? null,
        'Sales and sourcing' => $site->emails['sales'] ?? null,
        'Nutrition' => $site->emails['nutrition'] ?? null,
        'Suppliers' => $site->emails['partners'] ?? null,
    ])->filter();
    $whatsapp = collect(['WhatsApp (sales)' => $site->whatsapp_sales, 'WhatsApp (nutrition)' => $site->whatsapp_nutrition])->filter();

    $heading = $dark ? 'text-white' : 'text-green-900';
    $text = $dark ? 'text-on-dark-muted' : 'text-muted';
    $linkClass = $dark ? 'text-on-dark-muted hover:text-white' : 'text-ink hover:text-green-700';
@endphp
<footer {{ $attributes->class([$dark ? 'bg-green-800 text-on-dark' : 'border-t border-line bg-surface text-ink']) }}>
    <div class="mx-auto max-w-[84rem] px-4 pt-14 pb-10 sm:px-6 xl:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                @if ($dark)
                    <span class="inline-block rounded-2xl bg-white px-5 py-4 shadow-sm">
                        <x-brand.logo variant="full" class="h-auto w-64" />
                    </span>
                @else
                    <x-brand.logo variant="full" class="h-auto w-64" />
                @endif
                <p class="mt-5 max-w-xs text-base {{ $text }}">
                    International poultry feed nutrition, consulting and ingredient supply.
                </p>
            </div>

            @foreach ($columns as $title => $items)
                <nav aria-label="{{ $title }}">
                    <h2 class="text-sm font-extrabold tracking-[0.08em] uppercase {{ $heading }}">{{ $title }}</h2>
                    <span class="accent-bar mt-3"></span>
                    <ul class="mt-5 space-y-3 text-base">
                        @foreach ($items as $item)
                            <li><a href="{{ $item['href'] }}" class="{{ $linkClass }}">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            <div>
                <h2 class="text-sm font-extrabold tracking-[0.08em] uppercase {{ $heading }}">Contact</h2>
                <span class="accent-bar mt-3"></span>
                <dl class="mt-5 space-y-3 text-base">
                    @foreach ($mailboxes as $label => $email)
                        <div>
                            <dt class="text-sm {{ $text }}">{{ $label }}</dt>
                            <dd><a href="mailto:{{ $email }}" class="font-medium {{ $linkClass }}">{{ $email }}</a></dd>
                        </div>
                    @endforeach
                    @foreach ($whatsapp as $label => $number)
                        <div>
                            <dt class="text-sm {{ $text }}">{{ $label }}</dt>
                            <dd><a href="https://wa.me/{{ preg_replace('/\D+/', '', $number) }}" class="font-medium {{ $linkClass }}">{{ $number }}</a></dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <div @class(['mt-12 flex flex-col gap-4 border-t pt-6 text-sm lg:flex-row lg:items-start lg:justify-between', 'border-white/15' => $dark, 'border-line' => ! $dark])>
            <div class="max-w-3xl space-y-1 {{ $text }}">
                <p>{{ $site->statusStatement() }}</p>
                <p>&copy; {{ now()->year }} {{ $site->brand }}</p>
            </div>
            @if ($legal->isNotEmpty())
                <nav aria-label="Legal">
                    <ul class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach ($legal as $item)
                            <li><a href="{{ $item['href'] }}" class="{{ $linkClass }}">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>
</footer>
