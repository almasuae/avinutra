<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.samples_trials.heading')">
        @if ($samples !== null)
            <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::samples.plural') }}</h3>
            <ul style="margin:0.3rem 0 0.8rem;">
                @forelse ($samples as $sample)
                    <li style="display:flex; justify-content:space-between; gap:0.5rem;">
                        <span>{{ $sample->product?->name ?? '—' }} → {{ $sample->organisation?->name ?? '—' }}</span>
                        <span style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::samples.stages.'.$sample->stage()) }}</span>
                    </li>
                @empty
                    <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.samples_trials.no_samples') }}</li>
                @endforelse
            </ul>
        @endif
        @if ($trials !== null)
            <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::trials.plural') }}</h3>
            <ul style="margin-top:0.3rem;">
                @forelse ($trials as $trial)
                    <li style="display:flex; justify-content:space-between; gap:0.5rem;">
                        <span>{{ $trial->name }}</span>
                        <span style="font-size:0.8rem; opacity:0.7;">{{ $trial->status->getLabel() }}</span>
                    </li>
                @empty
                    <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.samples_trials.no_trials') }}</li>
                @endforelse
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
