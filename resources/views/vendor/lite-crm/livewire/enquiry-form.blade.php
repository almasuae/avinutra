{{-- Host override of the Lite CRM enquiry form (AviNutra brand styling; same behaviour). --}}
<div class="lite-crm-enquiry-form">
    @if ($submitted)
        <div role="status" class="rounded-(--radius-card) border border-green-500/40 bg-surface p-6 text-green-900">
            <p class="font-heading text-xl font-black">{{ __('lite-crm::enquiries.form.thanks_heading') }}</p>
            <p class="mt-1 text-sm">{{ __('lite-crm::enquiries.form.thanks') }}</p>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-5 sm:grid-cols-2" novalidate>
            @error('form')
                <p role="alert" class="rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-800 sm:col-span-2">{{ $message }}</p>
            @enderror

            @foreach ($definitions as $key => $field)
                @php($id = 'enquiry-'.$this->getId().'-'.$key)
                <div @class(['sm:col-span-2' => $field['type'] === 'textarea'])>
                    <label for="{{ $id }}" class="block text-sm font-semibold text-green-900">
                        {{ $field['label'] }}@if ($field['required'])<span aria-hidden="true" class="text-orange-text"> *</span>@endif
                    </label>

                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $id }}" wire:model="data.{{ $key }}" rows="5"
                            @if ($field['required']) required aria-required="true" @endif
                            @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                            class="mt-1.5 block w-full rounded-xl border border-line bg-white px-4 py-3 text-base text-ink shadow-xs focus:border-green-700 focus:outline-2 focus:outline-green-700/30"></textarea>
                    @elseif ($field['type'] === 'country')
                        {{-- Type-to-search country picker; the CRM stores the ISO code. --}}
                        <div wire:ignore x-data="{
                                label: @js(\LiteCrm\Support\Countries::name($this->data[$key] ?? null) ?? ''),
                                pick() {
                                    const typed = this.label.trim().toLowerCase();
                                    const match = Array.from(this.$refs.list.options).find((option) => option.value.toLowerCase() === typed);
                                    if (match) { this.label = match.value; }
                                    $wire.set('data.{{ $key }}', match ? match.dataset.code : '', false);
                                },
                            }">
                            <input id="{{ $id }}" type="text" list="{{ $id }}-list" x-model="label" x-on:change="pick()" x-on:blur="pick()"
                                autocomplete="country-name" placeholder="Start typing a country"
                                @if ($field['required']) required aria-required="true" @endif
                                @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                class="mt-1.5 block w-full rounded-xl border border-line bg-white px-4 py-3 text-base text-ink shadow-xs focus:border-green-700 focus:outline-2 focus:outline-green-700/30">
                            <datalist id="{{ $id }}-list" x-ref="list">
                                @foreach (\LiteCrm\Support\Countries::all() as $code => $name)
                                    <option value="{{ $name }}" data-code="{{ $code }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    @elseif ($field['type'] === 'select')
                        <select id="{{ $id }}" wire:model="data.{{ $key }}"
                            @if ($field['required']) required aria-required="true" @endif
                            @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                            class="mt-1.5 block w-full rounded-xl border border-line bg-white px-4 py-3 text-base text-ink shadow-xs focus:border-green-700 focus:outline-2 focus:outline-green-700/30">
                            <option value="">{{ __('lite-crm::enquiries.form.choose') }}</option>
                            @foreach ($field['options'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="{{ $id }}" type="{{ $field['type'] }}" wire:model="data.{{ $key }}"
                            @if ($field['type'] === 'email') autocomplete="email" @elseif ($key === 'name') autocomplete="name" @elseif ($field['type'] === 'tel') autocomplete="tel" @endif
                            @if ($field['required']) required aria-required="true" @endif
                            @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                            class="mt-1.5 block w-full rounded-xl border border-line bg-white px-4 py-3 text-base text-ink shadow-xs focus:border-green-700 focus:outline-2 focus:outline-green-700/30">
                    @endif

                    @error('data.'.$key)
                        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            @if ($maxUploads > 0)
                @php($uploadId = 'enquiry-'.$this->getId().'-attachments')
                <div class="sm:col-span-2">
                    <label for="{{ $uploadId }}" class="block text-sm font-semibold text-green-900">{{ __('lite-crm::enquiries.form.attachments') }}</label>
                    <input id="{{ $uploadId }}" type="file" wire:model="attachments" multiple
                        accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png"
                        aria-describedby="{{ $uploadId }}-help"
                        class="mt-1.5 block w-full rounded-xl border border-dashed border-line bg-surface p-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-green-800 file:px-4 file:py-2 file:font-semibold file:text-white">
                    <p id="{{ $uploadId }}-help" class="mt-1 text-sm text-muted">
                        {{ __('lite-crm::enquiries.form.attachments_help', ['count' => $maxUploads, 'size' => (int) round((int) config('lite-crm.documents.max_size_kb', 10240) / 1024)]) }}
                    </p>
                    @error('attachments')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endif

            {{-- Honeypot: hidden from people and assistive technology; bots fill it in. --}}
            <div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
                <label for="enquiry-{{ $this->getId() }}-website">{{ __('lite-crm::enquiries.form.honeypot') }}</label>
                <input id="enquiry-{{ $this->getId() }}-website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" wire:model="consent" class="mt-0.5 size-5 accent-green-700" @error('consent') aria-invalid="true" @enderror>
                    <span>
                        @if ($privacyUrl)
                            {!! __('lite-crm::enquiries.form.consent_with_link', ['link' => '<a href="'.e($privacyUrl).'" class="font-semibold text-green-700 underline">'.e(__('lite-crm::enquiries.form.privacy_policy')).'</a>']) !!}
                        @else
                            {{ __('lite-crm::enquiries.form.consent') }}
                        @endif
                    </span>
                </label>
                @error('consent')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
            <button type="submit" wire:loading.attr="disabled"
                class="btn-primary disabled:opacity-60">
                {{ $submitLabel ?? __('lite-crm::enquiries.form.submit') }}
            </button>
            </div>
        </form>
    @endif
</div>
