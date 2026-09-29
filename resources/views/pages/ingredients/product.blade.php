{{--
    Product page (v3 §7.4 template). Manufacturer only with recorded permission;
    certificates only when a verified document is attached in the CRM; the
    applications only once reviewed by the nutrition panel.
--}}
@php
    use App\Support\ProductPresentation;

    $statement = ProductPresentation::statement($product->availability);
    $requests = ProductPresentation::requests($product);
    $nutritionalFunction = $product->getCustomValue('nutritional_function');
    $origin = $product->getCustomValue('country_of_origin');
    $tdsDate = $product->getCustomValue('tds_date');
    $applications = $product->getCustomValue('applications_reviewed') ? $product->getCustomValue('applications') : null;
    $form = $product->getCustomValue('physical_form');
    $specification = collect($product->specification ?? [])->filter(fn ($row) => filled($row['parameter'] ?? null));
@endphp
<x-layouts.site :title="$product->name.' — '.$category['title'].' — AviNutra'" :description="$product->description">
    @push('head')
        {{-- Product structured data: no offer or price (quotations are individual), no brand unless permission is recorded. --}}
        <x-seo.json-ld :data="array_filter([
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description,
            'url' => route('ingredients.product', [$category['slug'], $product->slug]),
            'category' => $category['title'],
            'manufacturer' => $manufacturers->isNotEmpty() ? ['@type' => 'Organization', 'name' => $manufacturers->pluck('name')->join(', ')] : null,
        ])" />
    @endpush
    <x-page.hero
        :eyebrow="$category['title']"
        :title="$product->name"
        :lead="$product->description"
        :breadcrumbs="['Ingredients' => route('ingredients'), $category['title'] => route('ingredients.category', $category['slug']), $product->name => null]"
    >
        @if ($statement)
            <p class="mt-5 inline-block rounded-full bg-green-800 px-4 py-1.5 text-sm font-semibold text-white">{{ $statement }}</p>
        @endif
    </x-page.hero>

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.6fr_1fr] xl:px-8">
            <div class="space-y-12">
                @if (filled($nutritionalFunction))
                    <div>
                        <x-page.heading title="Nutritional function" />
                        <p class="mt-6 text-lg">{{ $nutritionalFunction }}</p>
                    </div>
                @endif

                @if ($specification->isNotEmpty())
                    <div>
                        <x-page.heading title="Typical specification" />
                        <div class="mt-6 overflow-x-auto rounded-(--radius-card) border border-line">
                            <table class="w-full text-left text-base">
                                <caption class="sr-only">Typical specification of {{ $product->name }}</caption>
                                <thead class="bg-surface text-sm text-green-900"><tr><th scope="col" class="px-4 py-3">Parameter</th><th scope="col" class="px-4 py-3">Value</th></tr></thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($specification as $row)
                                        <tr><td class="px-4 py-3">{{ $row['parameter'] }}</td><td class="px-4 py-3">{{ $row['value'] ?? '' }} {{ $row['unit'] ?? '' }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-sm text-muted">
                            From the manufacturer's technical data sheet{{ $tdsDate ? ' dated '.\Illuminate\Support\Carbon::parse($tdsDate)->format('j F Y') : '' }}. Always check the current TDS and the certificate of analysis for each batch.
                        </p>
                    </div>
                @endif

                @if ($form || filled($product->packaging))
                    <div>
                        <x-page.heading title="Forms and packaging" />
                        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                            @if ($form)
                                <div class="rounded-xl bg-surface p-5"><dt class="text-sm text-muted">Form</dt><dd class="mt-1 font-semibold">{{ ucfirst((string) $form) }}</dd></div>
                            @endif
                            @if (filled($product->packaging))
                                <div class="rounded-xl bg-surface p-5"><dt class="text-sm text-muted">Packaging</dt><dd class="mt-1 font-semibold">{{ $product->packaging }}</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if (filled($applications))
                    <div>
                        <x-page.heading title="Typical applications and inclusion guidance" />
                        <p class="mt-6 text-lg">{{ $applications }}</p>
                        <p class="mt-2 text-sm text-muted">Reviewed by our nutrition panel. Confirm inclusion with your nutritionist.</p>
                    </div>
                @endif

                @if (filled($product->storage) || filled($product->shelf_life) || filled($origin))
                    <div>
                        <x-page.heading title="Storage, shelf life and origin" />
                        <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                            @foreach (['Storage' => $product->storage, 'Shelf life' => $product->shelf_life, 'Country of origin' => $origin] as $label => $value)
                                @if (filled($value))
                                    <div class="rounded-xl bg-surface p-5">
                                        <dt class="text-sm text-muted">{{ $label }}</dt>
                                        <dd class="mt-1 font-semibold">{{ $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div>
                    <x-page.heading title="Manufacturer" />
                    @if ($manufacturers->isNotEmpty())
                        <p class="mt-6 text-lg">{{ $manufacturers->pluck('name')->join(', ') }}</p>
                    @else
                        <p class="mt-6 text-lg">International manufacturer — details on request</p>
                    @endif
                </div>

                @if ($certificates->isNotEmpty())
                    <div>
                        <x-page.heading title="Certifications" />
                        <ul class="mt-6 space-y-3">
                            @foreach ($certificates as $certificate)
                                <li class="rounded-xl border border-line p-5">
                                    <p class="font-semibold">{{ $certificate->title }}@if ($certificate->issuer) — {{ $certificate->issuer }}@endif</p>
                                    <p class="mt-1 text-sm text-muted">Certificate number {{ $certificate->certificate_number }}@if ($certificate->expires_on) · valid until {{ $certificate->expires_on->format('j F Y') }}@endif</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <aside class="space-y-6 self-start lg:sticky lg:top-28">
                <div class="card">
                    <h2 class="text-xl">Technical documents and requests</h2>
                    <p class="mt-2 text-base text-muted">Documents are sent on request and never published openly.</p>
                    <div class="mt-5 flex flex-col gap-3">
                        @foreach ($requests as [$label, $type, $extra])
                            <a href="{{ route('contact', ['type' => $type, 'product' => $product->name, ...$extra]) }}#enquiry" @class(['btn-primary' => $loop->first, 'btn-secondary' => ! $loop->first])>{{ $label }}</a>
                        @endforeach
                        <a href="{{ route('ask-a-nutritionist') }}" @class(['btn-primary' => $requests === [], 'btn-secondary' => $requests !== []])>Ask a Nutritionist</a>
                    </div>
                </div>
                @if ($category['key'] === 'amino_acids')
                    <div class="rounded-(--radius-card) bg-surface p-7">
                        <h2 class="text-xl">Related</h2>
                        <ul class="mt-3 space-y-2 text-base">
                            <li><a href="{{ route('ingredients.methionine') }}" class="font-semibold text-green-700 hover:underline">Methionine guide</a></li>
                            @if ($tools = \App\Support\SiteLinks::url('tools'))
                                <li><a href="{{ $tools }}" class="font-semibold text-green-700 hover:underline">Tools & Calculators</a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.site>
