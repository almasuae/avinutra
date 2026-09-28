<div class="lite-crm-enquiry-form">
    @if ($submitted)
        <div role="status" class="rounded-md border border-green-300 bg-green-50 p-4 text-green-900">
            <p class="font-medium">{{ __('lite-crm::enquiries.form.thanks_heading') }}</p>
            <p class="mt-1 text-sm">{{ __('lite-crm::enquiries.form.thanks') }}</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5" novalidate>
            @error('form')
                <p role="alert" class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</p>
            @enderror

            @foreach ($definitions as $key => $field)
                @php($id = 'enquiry-'.$this->getId().'-'.$key)
                <div>
                    <label for="{{ $id }}" class="block text-sm font-medium">
                        {{ $field['label'] }}@if ($field['required'])<span aria-hidden="true"> *</span>@endif
                    </label>

                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $id }}" wire:model="data.{{ $key }}" rows="5"
                            @if ($field['required']) required aria-required="true" @endif
                            @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
                    @elseif ($field['type'] === 'select')
                        <select id="{{ $id }}" wire:model="data.{{ $key }}"
                            @if ($field['required']) required aria-required="true" @endif
                            @error('data.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2">
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
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2">
                    @endif

                    @error('data.'.$key)
                        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            @if ($maxUploads > 0)
                @php($uploadId = 'enquiry-'.$this->getId().'-attachments')
                <div>
                    <label for="{{ $uploadId }}" class="block text-sm font-medium">{{ __('lite-crm::enquiries.form.attachments') }}</label>
                    <input id="{{ $uploadId }}" type="file" wire:model="attachments" multiple
                        accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png"
                        aria-describedby="{{ $uploadId }}-help"
                        class="mt-1 block w-full text-sm">
                    <p id="{{ $uploadId }}-help" class="mt-1 text-sm text-gray-600">
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

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="consent" class="mt-1" @error('consent') aria-invalid="true" @enderror>
                    <span>
                        @if ($privacyUrl)
                            {!! __('lite-crm::enquiries.form.consent_with_link', ['link' => '<a href="'.e($privacyUrl).'" class="underline">'.e(__('lite-crm::enquiries.form.privacy_policy')).'</a>']) !!}
                        @else
                            {{ __('lite-crm::enquiries.form.consent') }}
                        @endif
                    </span>
                </label>
                @error('consent')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="rounded-md bg-primary px-5 py-2.5 font-medium text-white hover:bg-primary-dark disabled:opacity-60">
                {{ $submitLabel ?? __('lite-crm::enquiries.form.submit') }}
            </button>
        </form>
    @endif
</div>
