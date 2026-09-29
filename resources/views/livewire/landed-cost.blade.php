{{-- Tool 2 — Landed Cost Calculator (universal; owner's specification of 29 Sep 2026). --}}
@php
    $fmt = fn (float $value, int $decimals = 2): string => number_format($value, $decimals);
    $outcome = $this->outcome;
    $result = $outcome['result'];
    $local = $form['local_currency'] ?: 'local';
    $purchase = $form['purchase_currency'] ?: 'USD';
    $input = 'mt-1.5 block w-full rounded-xl border border-line bg-white px-3 py-2.5 text-base text-ink focus:border-green-700 focus:outline-2 focus:outline-green-700/30';
    $label = 'text-sm font-semibold text-green-900';
    $legend = 'float-left mb-2 w-full font-heading text-xl font-black text-green-900';
@endphp
<div class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
    <div class="min-w-0 space-y-6">
        <div class="no-print flex flex-wrap gap-3">
            <button type="button" wire:click="loadExample" class="btn-secondary">Example</button>
            <button type="button" wire:click="resetForm" class="btn border-2 border-line text-green-900 hover:border-green-700">Clear</button>
        </div>
        @if ($example)
            <p role="status" class="rounded-xl border border-orange-500/40 bg-orange-400/10 px-4 py-3 text-sm font-semibold text-orange-text">Example only — illustrative values, not the rates of any country.</p>
        @endif

        {{-- Quantity --}}
        <fieldset class="card grid gap-4 sm:grid-cols-2">
            <legend class="{{ $legend }} sm:col-span-2">Quantity</legend>
            <div>
                <label for="lc-qty" class="{{ $label }}">Quantity</label>
                <input id="lc-qty" type="number" step="any" min="0" wire:model.blur="form.quantity" class="{{ $input }}">
            </div>
            <div>
                <label for="lc-unit" class="{{ $label }}">Unit</label>
                <select id="lc-unit" wire:model.live="form.quantity_unit" class="{{ $input }}">
                    <option value="kg">kg</option>
                    <option value="mt">tonnes (MT)</option>
                    <option value="bags">bags</option>
                    <option value="containers">containers</option>
                </select>
            </div>
            @if ($form['quantity_unit'] === 'bags')
                <div>
                    <label for="lc-bag" class="{{ $label }}">Net kg per bag</label>
                    <input id="lc-bag" type="number" step="any" min="0" wire:model.blur="form.kg_per_bag" class="{{ $input }}">
                </div>
            @endif
            <div>
                <label for="lc-kgc" class="{{ $label }}">Net kg per container</label>
                <input id="lc-kgc" type="number" step="any" min="0" wire:model.blur="form.kg_per_container" class="{{ $input }}" placeholder="for per-container charges">
            </div>
        </fieldset>

        {{-- Currencies --}}
        <fieldset class="card grid gap-4 sm:grid-cols-2">
            <legend class="{{ $legend }} sm:col-span-2">Currencies</legend>
            <div>
                <label for="lc-pcur" class="{{ $label }}">Purchase currency (ISO code)</label>
                <input id="lc-pcur" type="text" maxlength="3" autocomplete="off" wire:model.blur="form.purchase_currency" class="{{ $input }} uppercase placeholder:normal-case" placeholder="USD">
            </div>
            <div>
                <label for="lc-lcur" class="{{ $label }}">Local currency (ISO code)</label>
                <input id="lc-lcur" type="text" maxlength="3" autocomplete="off" wire:model.blur="form.local_currency" class="{{ $input }} uppercase placeholder:normal-case" placeholder="e.g. EUR">
            </div>
            <div>
                <label for="lc-fx" class="{{ $label }}">Exchange rate ({{ $local }} per 1 {{ $purchase }})</label>
                <input id="lc-fx" type="number" step="any" min="0" wire:model.blur="form.exchange_rate" class="{{ $input }}">
            </div>
            <div>
                <label for="lc-fxd" class="{{ $label }}">Rate date</label>
                <input id="lc-fxd" type="date" wire:model.blur="form.rate_date" class="{{ $input }}">
            </div>
        </fieldset>

        {{-- Price and basis --}}
        <fieldset class="card grid gap-4 sm:grid-cols-3">
            <legend class="{{ $legend }} sm:col-span-3">Price</legend>
            <div>
                <label for="lc-price" class="{{ $label }}">Price ({{ $purchase }})</label>
                <input id="lc-price" type="number" step="any" min="0" wire:model.blur="form.price" class="{{ $input }}">
            </div>
            <div>
                <label for="lc-punit" class="{{ $label }}">Per</label>
                <select id="lc-punit" wire:model.live="form.price_unit" class="{{ $input }}">
                    <option value="kg">kg</option>
                    <option value="mt">tonne (MT)</option>
                </select>
            </div>
            <div>
                <label for="lc-basis" class="{{ $label }}">Price basis (Incoterm)</label>
                <select id="lc-basis" wire:model.live="form.basis" class="{{ $input }}">
                    @foreach (\App\Services\Calculators\LandedCostCalculator::BASES as $basis)
                        <option value="{{ $basis }}">{{ $basis }}</option>
                    @endforeach
                </select>
            </div>
            @if ($needsFreight)
                <div>
                    <label for="lc-freight" class="{{ $label }}">Freight ({{ $purchase }})</label>
                    <input id="lc-freight" type="number" step="any" min="0" wire:model.blur="form.freight_amount" class="{{ $input }}">
                </div>
                <div>
                    <label for="lc-freightper" class="{{ $label }}">Freight per</label>
                    <select id="lc-freightper" wire:model.live="form.freight_per" class="{{ $input }}">
                        <option value="shipment">shipment</option>
                        <option value="container">container</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
            @endif
            @if ($needsInsurance)
                <div class="sm:col-span-2">
                    <label for="lc-instype" class="{{ $label }}">Insurance as</label>
                    <select id="lc-instype" wire:model.live="form.insurance_type" class="{{ $input }}">
                        <option value="percent">% of goods + freight</option>
                        <option value="amount">fixed amount ({{ $purchase }})</option>
                    </select>
                </div>
                <div>
                    <label for="lc-ins" class="{{ $label }}">Insurance ({{ $form['insurance_type'] === 'amount' ? $purchase.' per shipment' : '%' }})</label>
                    <input id="lc-ins" type="number" step="any" min="0" wire:model.blur="form.insurance_value" class="{{ $input }}">
                </div>
            @endif
            @unless ($needsFreight || $needsInsurance)
                <p class="text-sm text-muted sm:col-span-3">A {{ $form['basis'] }} price already includes freight and insurance.</p>
            @endunless
        </fieldset>

        {{-- Customs valuation and duties --}}
        <fieldset class="card space-y-4">
            <legend class="{{ $legend }}">Customs valuation and duties</legend>
            <div class="clear-both grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="lc-val" class="{{ $label }}">Customs valuation basis</label>
                    <select id="lc-val" wire:model.live="form.valuation" class="{{ $input }}">
                        <option value="CIF">CIF (most countries)</option>
                        <option value="FOB">FOB</option>
                    </select>
                </div>
                @if ($form['valuation'] === 'FOB' && ! $needsFreight)
                    <div>
                        <label for="lc-fincl" class="{{ $label }}">Freight included in the price ({{ $purchase }})</label>
                        <input id="lc-fincl" type="number" step="any" min="0" wire:model.blur="form.freight_included" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="lc-iincl" class="{{ $label }}">Insurance included in the price ({{ $purchase }})</label>
                        <input id="lc-iincl" type="number" step="any" min="0" wire:model.blur="form.insurance_included" class="{{ $input }}">
                    </div>
                @endif
            </div>
            @foreach ($form['duties'] as $i => $duty)
                <div wire:key="duty-{{ $i }}" class="grid gap-3 rounded-xl bg-surface p-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="lc-dn-{{ $i }}" class="{{ $label }}">Duty name</label>
                        <input id="lc-dn-{{ $i }}" type="text" wire:model.blur="form.duties.{{ $i }}.name" class="{{ $input }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="lc-dr-{{ $i }}" class="{{ $label }}">Rate</label>
                        <input id="lc-dr-{{ $i }}" type="number" step="any" min="0" wire:model.blur="form.duties.{{ $i }}.rate" class="{{ $input }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="lc-dt-{{ $i }}" class="{{ $label }}">Rate type</label>
                        <select id="lc-dt-{{ $i }}" wire:model.live="form.duties.{{ $i }}.type" class="{{ $input }}">
                            <option value="percent">%</option>
                            <option value="per_kg">{{ $local }} per kg</option>
                            <option value="per_mt">{{ $local }} per tonne</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="lc-db-{{ $i }}" class="{{ $label }}">Base</label>
                        <select id="lc-db-{{ $i }}" wire:model.live="form.duties.{{ $i }}.base" class="{{ $input }}">
                            <option value="customs">customs value</option>
                            <option value="cumulative">customs value + previous duties</option>
                        </select>
                    </div>
                    <button type="button" wire:click="removeRow('duties', {{ $i }})" class="no-print self-end pb-3 sm:col-span-1 text-sm font-semibold text-muted underline hover:text-green-700">Remove</button>
                </div>
            @endforeach
            <button type="button" wire:click="addRow('duties')" class="no-print text-sm font-semibold text-green-700 underline">+ Add a duty line</button>
        </fieldset>

        {{-- Taxes --}}
        <fieldset class="card space-y-4">
            <legend class="{{ $legend }}">Taxes</legend>
            <div class="clear-both grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="lc-vat" class="{{ $label }}">VAT / GST / sales tax (%)</label>
                    <input id="lc-vat" type="number" step="any" min="0" wire:model.blur="form.vat_rate" class="{{ $input }}">
                </div>
                <div>
                    <label for="lc-vatb" class="{{ $label }}">Base</label>
                    <select id="lc-vatb" wire:model.live="form.vat_base" class="{{ $input }}">
                        <option value="customs_duties">customs value + duties</option>
                        <option value="customs">customs value</option>
                    </select>
                </div>
                <label class="flex items-end gap-2 pb-3 text-sm font-semibold text-green-900">
                    <input type="checkbox" wire:model.live="form.vat_recoverable" class="size-5 accent-green-700"> Recoverable
                </label>
            </div>
            @foreach ($form['other_taxes'] as $i => $tax)
                <div wire:key="tax-{{ $i }}" class="grid gap-3 rounded-xl bg-surface p-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="lc-tn-{{ $i }}" class="{{ $label }}">Tax name</label>
                        <input id="lc-tn-{{ $i }}" type="text" wire:model.blur="form.other_taxes.{{ $i }}.name" class="{{ $input }}" placeholder="e.g. withholding tax">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="lc-tr-{{ $i }}" class="{{ $label }}">Rate (%)</label>
                        <input id="lc-tr-{{ $i }}" type="number" step="any" min="0" wire:model.blur="form.other_taxes.{{ $i }}.rate" class="{{ $input }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="lc-tb-{{ $i }}" class="{{ $label }}">Base</label>
                        <select id="lc-tb-{{ $i }}" wire:model.live="form.other_taxes.{{ $i }}.base" class="{{ $input }}">
                            <option value="customs_duties_vat">customs value + duties + VAT</option>
                            <option value="customs_duties">customs value + duties</option>
                            <option value="customs">customs value</option>
                        </select>
                    </div>
                    <label class="flex items-end gap-2 pb-3 text-sm font-semibold text-green-900 sm:col-span-2">
                        <input type="checkbox" wire:model.live="form.other_taxes.{{ $i }}.recoverable" class="size-5 accent-green-700"> Recoverable / adjustable
                    </label>
                    <button type="button" wire:click="removeRow('other_taxes', {{ $i }})" class="no-print self-end pb-3 text-sm font-semibold text-muted underline hover:text-green-700">Remove</button>
                </div>
            @endforeach
            <button type="button" wire:click="addRow('other_taxes')" class="no-print text-sm font-semibold text-green-700 underline">+ Add another import tax</button>
        </fieldset>

        {{-- Local charges --}}
        <fieldset class="card space-y-3">
            <legend class="{{ $legend }}">Local charges ({{ $local }})</legend>
            @foreach ($form['charges'] as $i => $charge)
                <div wire:key="charge-{{ $i }}" class="clear-both grid gap-3 sm:grid-cols-[1.6fr_1fr_1fr_auto]">
                    <div>
                        <label for="lc-cn-{{ $i }}" class="{{ $label }}">Charge</label>
                        <input id="lc-cn-{{ $i }}" type="text" wire:model.blur="form.charges.{{ $i }}.name" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="lc-ca-{{ $i }}" class="{{ $label }}">Amount ({{ $local }})</label>
                        <input id="lc-ca-{{ $i }}" type="number" step="any" min="0" wire:model.blur="form.charges.{{ $i }}.amount" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="lc-cp-{{ $i }}" class="{{ $label }}">Per</label>
                        <select id="lc-cp-{{ $i }}" wire:model.live="form.charges.{{ $i }}.per" class="{{ $input }}">
                            <option value="shipment">shipment</option>
                            <option value="container">container</option>
                        </select>
                    </div>
                    <button type="button" wire:click="removeRow('charges', {{ $i }})" class="no-print self-end pb-3 text-sm font-semibold text-muted underline hover:text-green-700">Remove</button>
                </div>
            @endforeach
            <button type="button" wire:click="addRow('charges')" class="no-print text-sm font-semibold text-green-700 underline">+ Add a charge</button>
        </fieldset>

        {{-- Finance --}}
        <fieldset class="card grid gap-4 sm:grid-cols-3">
            <legend class="{{ $legend }} sm:col-span-3">Bank and financing</legend>
            <div>
                <label for="lc-bank" class="{{ $label }}">Bank / LC charges (% of CIF)</label>
                <input id="lc-bank" type="number" step="any" min="0" wire:model.blur="form.bank_percent" class="{{ $input }}">
            </div>
            <div>
                <label for="lc-fin" class="{{ $label }}">Financing cost (% a year)</label>
                <input id="lc-fin" type="number" step="any" min="0" wire:model.blur="form.financing_rate" class="{{ $input }}">
            </div>
            <div>
                <label for="lc-days" class="{{ $label }}">Financing period (days)</label>
                <input id="lc-days" type="number" step="1" min="0" wire:model.blur="form.financing_days" class="{{ $input }}">
            </div>
        </fieldset>
    </div>

    {{-- Results --}}
    <div class="min-w-0 space-y-6 lg:sticky lg:top-28 lg:self-start" aria-live="polite">
        <div class="card">
            <h2 class="text-2xl">Landed cost</h2>
            @if ($result === null)
                <p class="mt-4 text-base text-muted">{{ $outcome['problem'] }}</p>
            @else
                <div class="mt-5 overflow-x-auto rounded-xl border border-line">
                    <table class="w-full min-w-[30rem] text-left text-sm">
                        <caption class="sr-only">Landed cost per kg, per tonne and per shipment</caption>
                        <thead class="bg-surface text-green-900">
                            <tr><th scope="col" class="px-3 py-2"></th><th scope="col" class="px-3 py-2 text-right">per kg</th><th scope="col" class="px-3 py-2 text-right">per tonne</th><th scope="col" class="px-3 py-2 text-right">per shipment</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach (['gross' => 'Gross', 'net' => 'Net of recoverable taxes'] as $key => $title)
                                @foreach ([$purchase => 'purchase', $local => 'local'] as $code => $currencyKey)
                                    <tr @class(['font-semibold' => $key === 'net'])>
                                        <th scope="row" class="px-3 py-2">{{ $title }} ({{ $code }})</th>
                                        <td class="px-3 py-2 text-right">{{ $fmt($result[$key][$currencyKey]['kg'], 3) }}</td>
                                        <td class="px-3 py-2 text-right">{{ $fmt($result[$key][$currencyKey]['mt']) }}</td>
                                        <td class="px-3 py-2 text-right">{{ $fmt($result[$key][$currencyKey]['shipment']) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-muted">
                    {{ $fmt($result['kg'], 0) }} kg{{ $result['containers'] > 0 ? ' · '.$fmt($result['containers'], 0).' container(s)' : '' }}
                    · rate {{ $form['exchange_rate'] }} {{ $local }} per {{ $purchase }}{{ $form['rate_date'] ? ' ('.\Illuminate\Support\Carbon::parse($form['rate_date'])->format('j M Y').')' : '' }}
                </p>

                <h3 class="mt-6 text-lg">Breakdown ({{ $local }})</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Line-by-line breakdown per shipment and per kg</caption>
                        <thead class="text-muted"><tr><th scope="col" class="py-1">Line</th><th scope="col" class="py-1 text-right">per shipment</th><th scope="col" class="py-1 text-right">per kg</th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($result['lines'] as $line)
                                <tr>
                                    <th scope="row" class="py-2 font-normal">{{ $line['label'] }}@if ($line['recoverable']) <span class="text-xs text-muted">(recoverable)</span>@endif</th>
                                    <td class="py-2 text-right">{{ $fmt($line['amount']) }}</td>
                                    <td class="py-2 text-right">{{ $fmt($line['per_kg'], 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="no-print mt-6 flex flex-wrap gap-3">
                <button type="button" wire:click="$toggle('working')" class="btn-secondary" aria-expanded="{{ $working ? 'true' : 'false' }}" aria-controls="lc-working">{{ $working ? 'Hide working' : 'Show working' }}</button>
                <button type="button" onclick="window.print()" class="btn border-2 border-line text-green-900 hover:border-green-700">Print</button>
                @if ($discuss = $this->discussUrl())
                    <a href="{{ $discuss }}" class="btn-primary">Discuss your result <x-heroicon-m-arrow-right class="size-5" aria-hidden="true" /></a>
                @endif
            </div>
        </div>

        @if ($working && $result !== null)
            <div id="lc-working" class="card text-sm">
                <h2 class="text-xl">Working</h2>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5 font-mono text-[0.8rem] break-words">
                    @foreach ($result['working'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        <p class="rounded-xl bg-surface px-5 py-4 text-sm">Duty and tax rates change and differ by product and country. Confirm every rate with a licensed customs broker before relying on the result.</p>
    </div>
</div>
