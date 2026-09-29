{{-- Tool 1 — Methionine Value Calculator (v3 §7.8; presets per the owner's decision of 29 Sep 2026). --}}
@php
    $fmt = fn (float $value, int $decimals = 3): string => number_format($value, $decimals);
    // Value factors with at least two decimals: 1.00, 0.70, 0.909.
    $factorText = fn (float $factor): string => number_format($factor, max(2, strlen(rtrim(substr(strrchr(number_format($factor, 3, '.', ''), '.'), 1), '0'))));
    $outcome = $this->outcome;
    $result = $outcome['result'];
    $rate = $outcome['rate'];
    $currency = $outcome['currency'];
    $bases = collect($products)->pluck('basis')->unique();
    $input = 'mt-1.5 block w-full rounded-xl border border-line bg-white px-3 py-2.5 text-base text-ink focus:border-green-700 focus:outline-2 focus:outline-green-700/30';
@endphp
<div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
    {{-- Inputs --}}
    <div class="min-w-0 space-y-6">
        <div class="no-print flex flex-wrap gap-3">
            <button type="button" wire:click="loadExample" data-example class="btn-secondary">Example</button>
            <button type="button" wire:click="resetForm" class="btn border-2 border-line text-green-900 hover:border-green-700">Clear</button>
        </div>
        @if ($example)
            <p role="status" class="rounded-xl border border-orange-500/40 bg-orange-400/10 px-4 py-3 text-sm font-semibold text-orange-text">Example only — illustrative prices, not market prices.</p>
        @endif

        <fieldset class="card space-y-5">
            <legend class="float-left mb-2 w-full font-heading text-xl font-black text-green-900">Currency</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="mv-currency" class="text-sm font-semibold text-green-900">Price currency (ISO code)</label>
                    <input id="mv-currency" type="text" wire:model.blur="currency" maxlength="3" autocomplete="off" class="{{ $input }} uppercase placeholder:normal-case" placeholder="USD">
                </div>
                <div>
                    <label for="mv-alt" class="text-sm font-semibold text-green-900">Also show in (ISO code, optional)</label>
                    <input id="mv-alt" type="text" wire:model.blur="altCurrency" maxlength="3" autocomplete="off" class="{{ $input }} uppercase placeholder:normal-case" placeholder="e.g. EUR">
                </div>
                @if (strlen($altCurrency) === 3)
                    <div>
                        <label for="mv-fx" class="text-sm font-semibold text-green-900">Exchange rate ({{ $altCurrency }} per 1 {{ $currency ?: 'USD' }})</label>
                        <input id="mv-fx" type="number" step="any" min="0" wire:model.blur="altRate" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="mv-fxd" class="text-sm font-semibold text-green-900">Rate date</label>
                        <input id="mv-fxd" type="date" wire:model.blur="altRateDate" class="{{ $input }}">
                    </div>
                @endif
            </div>
        </fieldset>

        @foreach ($products as $index => $row)
            @php($preset = $this->presets[$row['preset']])
            <fieldset wire:key="product-{{ $index }}" class="card space-y-4">
                <legend class="float-left mb-1 flex w-full items-center justify-between gap-3">
                    <span class="font-heading text-xl font-black text-green-900">Product {{ $index + 1 }}</span>
                    @if (count($products) > \App\Livewire\MethionineValue::MIN_PRODUCTS)
                        <button type="button" wire:click="removeProduct({{ $index }})" class="no-print text-sm font-semibold text-muted underline hover:text-green-700">Remove</button>
                    @endif
                </legend>
                <div class="clear-both">
                    <label for="mv-preset-{{ $index }}" class="text-sm font-semibold text-green-900">Product type</label>
                    <select id="mv-preset-{{ $index }}" wire:model.live="products.{{ $index }}.preset" class="{{ $input }}">
                        @foreach ($this->presets as $key => $option)
                            <option value="{{ $key }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($row['preset'] === 'custom')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="mv-name-{{ $index }}" class="text-sm font-semibold text-green-900">Product name</label>
                            <input id="mv-name-{{ $index }}" type="text" wire:model.blur="products.{{ $index }}.name" maxlength="80" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="mv-factor-{{ $index }}" class="text-sm font-semibold text-green-900">Value factor (kg DL-Met 99% per kg)</label>
                            <input id="mv-factor-{{ $index }}" type="number" step="any" min="0" wire:model.blur="products.{{ $index }}.factor" class="{{ $input }}">
                        </div>
                    </div>
                    <p class="text-sm text-muted">Use the manufacturer's documented value.</p>
                @else
                    <p class="text-sm text-muted">
                        Value factor <strong class="text-ink">{{ $factorText((float) $preset['factor']) }}</strong> — {{ $preset['note'] }}
                        @unless ($preset['approved'])
                            <span class="ml-1 inline-block rounded-full bg-surface px-2 py-0.5 text-xs font-semibold text-orange-text">Indicative default</span>
                        @endunless
                    </p>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label for="mv-price-{{ $index }}" class="text-sm font-semibold text-green-900">Price ({{ $currency ?: 'USD' }})</label>
                        <input id="mv-price-{{ $index }}" type="number" step="any" min="0" wire:model.blur="products.{{ $index }}.price" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="mv-unit-{{ $index }}" class="text-sm font-semibold text-green-900">Per</label>
                        <select id="mv-unit-{{ $index }}" wire:model.live="products.{{ $index }}.unit" class="{{ $input }}">
                            <option value="kg">kg</option>
                            <option value="mt">tonne (MT)</option>
                        </select>
                    </div>
                    <div>
                        <label for="mv-basis-{{ $index }}" class="text-sm font-semibold text-green-900">Price basis</label>
                        <select id="mv-basis-{{ $index }}" wire:model.live="products.{{ $index }}.basis" class="{{ $input }}">
                            <option value="CFR">CFR</option>
                            <option value="landed">Landed</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-green-900">
                    <input type="radio" name="mv-reference" value="{{ $index }}" wire:model.live="reference" class="size-4 accent-green-700">
                    Reference product (the one you use now)
                </label>
            </fieldset>
        @endforeach

        @if (count($products) < \App\Livewire\MethionineValue::MAX_PRODUCTS)
            <div class="no-print">
                <button type="button" wire:click="addProduct" class="btn-secondary">Add a product</button>
            </div>
        @endif

        <fieldset class="card grid gap-4 sm:grid-cols-2">
            <legend class="float-left mb-2 w-full font-heading text-xl font-black text-green-900 sm:col-span-2">Your feed</legend>
            <div>
                <label for="mv-inclusion" class="text-sm font-semibold text-green-900">Current inclusion of the reference product (kg per tonne of feed)</label>
                <input id="mv-inclusion" type="number" step="any" min="0" wire:model.blur="inclusion" class="{{ $input }}">
            </div>
            <div>
                <label for="mv-feed" class="text-sm font-semibold text-green-900">Monthly feed production (tonnes)</label>
                <input id="mv-feed" type="number" step="any" min="0" wire:model.blur="monthlyFeed" class="{{ $input }}">
            </div>
        </fieldset>
    </div>

    {{-- Results --}}
    <div class="min-w-0 space-y-6 lg:sticky lg:top-28 lg:self-start" aria-live="polite">
        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl">Results</h2>
                @if (strlen($altCurrency) === 3 && is_numeric($altRate) && (float) $altRate > 0)
                    <div class="no-print flex rounded-full border border-line p-1 text-sm font-semibold" role="group" aria-label="Currency shown">
                        <button type="button" wire:click="$set('display', 'main')" @class(['rounded-full px-3 py-1', 'bg-green-800 text-white' => $display !== 'alt']) aria-pressed="{{ $display !== 'alt' ? 'true' : 'false' }}">{{ $currency }}</button>
                        <button type="button" wire:click="$set('display', 'alt')" @class(['rounded-full px-3 py-1', 'bg-green-800 text-white' => $display === 'alt']) aria-pressed="{{ $display === 'alt' ? 'true' : 'false' }}">{{ $altCurrency }}</button>
                    </div>
                @endif
            </div>

            @if ($result === null)
                <ul class="mt-4 list-disc space-y-1 pl-5 text-base text-muted">
                    @foreach ($outcome['problems'] as $problem)
                        <li>{{ $problem }}</li>
                    @endforeach
                </ul>
            @else
                @if ($bases->count() > 1)
                    <p class="mt-3 rounded-xl bg-orange-400/10 px-4 py-2 text-sm text-orange-text">The prices are on different bases (CFR and landed). Compare them on the same basis.</p>
                @endif
                @if ($rate !== 1.0)
                    <p class="mt-3 text-sm text-muted">Shown in {{ $currency }} at {{ $altRate }} per 1 {{ $this->currency }}@if ($altRateDate) (rate of {{ \Illuminate\Support\Carbon::parse($altRateDate)->format('j M Y') }})@endif.</p>
                @endif
                {{-- Phones and small tablets: one small card per product. Wider screens: a table. --}}
                <ul class="mt-5 space-y-3 md:hidden">
                    @foreach ($result['rows'] as $row)
                        <li @class(['rounded-xl border border-line p-4', 'bg-green-500/5' => $row['is_reference']])>
                            <p class="text-sm font-semibold text-green-900">{{ $row['name'] }}@if ($row['is_reference']) <span class="font-normal text-muted">· Reference</span>@endif</p>
                            <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div><dt class="text-xs text-muted">Cost per kg effective Met ({{ $currency }})</dt><dd class="font-semibold tabular-nums">{{ $fmt($row['cost_per_kg_effective'] * $rate) }}</dd></div>
                                <div><dt class="text-xs text-muted">Inclusion (kg/t)</dt><dd class="font-semibold tabular-nums">{{ $fmt($row['equivalent_inclusion']) }}</dd></div>
                                <div><dt class="text-xs text-muted">Cost per t of feed ({{ $currency }})</dt><dd class="font-semibold tabular-nums">{{ $fmt($row['cost_per_mt_feed'] * $rate) }}</dd></div>
                                <div><dt class="text-xs text-muted">Difference</dt><dd class="font-semibold tabular-nums">{{ $row['is_reference'] ? '—' : ($row['difference_percent'] >= 0 ? '+' : '').$fmt($row['difference_percent'], 1).'%' }}</dd></div>
                            </dl>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5 hidden rounded-xl border border-line md:block">
                    <table data-results-table class="w-full text-left text-sm tabular-nums">
                        <caption class="sr-only">Comparison of methionine sources</caption>
                        <thead class="bg-surface text-green-900">
                            <tr>
                                <th scope="col" class="px-3 py-2">Product</th>
                                <th scope="col" class="px-3 py-2 text-right">Cost per kg effective Met ({{ $currency }})</th>
                                <th scope="col" class="px-3 py-2 text-right">Inclusion (kg/t)</th>
                                <th scope="col" class="px-3 py-2 text-right">Cost per t of feed ({{ $currency }})</th>
                                <th scope="col" class="px-3 py-2 text-right">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($result['rows'] as $row)
                                <tr @class(['bg-green-500/5' => $row['is_reference']])>
                                    <th scope="row" class="px-3 py-2 font-semibold">{{ $row['name'] }}@if ($row['is_reference']) <span class="block text-xs font-normal text-muted">Reference</span>@endif</th>
                                    <td class="px-3 py-2 text-right font-semibold">{{ $fmt($row['cost_per_kg_effective'] * $rate) }}</td>
                                    <td class="px-3 py-2 text-right">{{ $fmt($row['equivalent_inclusion']) }}</td>
                                    <td class="px-3 py-2 text-right">{{ $fmt($row['cost_per_mt_feed'] * $rate) }}</td>
                                    <td class="px-3 py-2 text-right">@if ($row['is_reference'])—@else{{ $row['difference_percent'] >= 0 ? '+' : '' }}{{ $fmt($row['difference_percent'], 1) }}%@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h3 class="mt-6 text-lg">Difference against the reference</h3>
                <dl class="mt-3 divide-y divide-line text-sm">
                    @foreach ($result['rows'] as $row)
                        @continue($row['is_reference'])
                        <div class="grid grid-cols-2 gap-2 py-2 sm:grid-cols-4">
                            <dt class="col-span-2 font-semibold sm:col-span-1">{{ $row['name'] }}</dt>
                            <dd>per t of feed: <strong>{{ $fmt($row['difference_per_mt_feed'] * $rate) }}</strong></dd>
                            <dd>per month: <strong>{{ number_format($row['difference_per_month'] * $rate, 0) }}</strong></dd>
                            <dd>per year: <strong>{{ number_format($row['difference_per_year'] * $rate, 0) }}</strong> {{ $currency }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-3 text-xs text-muted">A positive difference means the product costs more than the reference; a negative one, less.</p>
            @endif

            <div class="no-print mt-6 flex flex-wrap gap-3">
                <button type="button" wire:click="$toggle('working')" class="btn-secondary" aria-expanded="{{ $working ? 'true' : 'false' }}" aria-controls="mv-working">{{ $working ? 'Hide working' : 'Show working' }}</button>
                <button type="button" data-print class="btn border-2 border-line text-green-900 hover:border-green-700">Print</button>
                @if ($discuss = $this->discussUrl())
                    <a href="{{ $discuss }}" class="btn-primary">Discuss your result <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                @endif
            </div>
        </div>

        @if ($working)
            <div id="mv-working" class="card space-y-3 text-sm">
                <h2 class="text-xl">Working</h2>
                <p class="font-mono">cost per kg of effective methionine = price per kg ÷ value factor</p>
                <p class="font-mono">equivalent inclusion = reference inclusion × reference factor ÷ product factor</p>
                <p class="font-mono">cost per tonne of feed = equivalent inclusion × price per kg</p>
                <p class="font-mono">MHA-FA value factor ≈ 0.88 × equimolar efficacy × ({{ \App\Services\Calculators\MethionineValueCalculator::MOLAR_MASS_METHIONINE }} ÷ {{ \App\Services\Calculators\MethionineValueCalculator::MOLAR_MASS_HMTBA }})</p>
                <ul class="list-disc space-y-1 pl-5 text-muted">
                    <li>about 75% equimolar: 0.88 × 0.75 × 0.993 = {{ number_format(\App\Services\Calculators\MethionineValueCalculator::mhaFactor(0.75), 3) }} → preset 0.65</li>
                    <li>about 80% equimolar: 0.88 × 0.80 × 0.993 = {{ number_format(\App\Services\Calculators\MethionineValueCalculator::mhaFactor(0.80), 3) }} → preset 0.70</li>
                    <li>100% equimolar: the manufacturers' figure of 0.88 (1 kg of active substance = 1 kg of DL-Met 99%)</li>
                </ul>
                @if ($result !== null)
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($result['rows'] as $row)
                            <li>{{ $row['name'] }}: {{ $fmt($row['price_per_kg'], 4) }} ÷ {{ $factorText($row['factor']) }} = {{ $fmt($row['cost_per_kg_effective']) }} per kg effective; {{ $inclusion }} × {{ $factorText($result['rows'][$result['reference']]['factor']) }} ÷ {{ $factorText($row['factor']) }} = {{ $fmt($row['equivalent_inclusion']) }} kg/t; × {{ $fmt($row['price_per_kg'], 4) }} = {{ $fmt($row['cost_per_mt_feed']) }} per t of feed ({{ $this->currency ?: 'USD' }})</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <p class="rounded-xl bg-surface px-5 py-4 text-sm">This is an indicative economic comparison. Nutritional equivalence depends on the product, the diet and the formulation basis (total or digestible). Confirm with the manufacturer's technical documentation and your nutritionist.</p>
    </div>
</div>
