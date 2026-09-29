<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.expiring.heading', ['days' => $days])">
        <ul>
            @forelse ($documents as $document)
                <li style="display:flex; justify-content:space-between; gap:0.5rem; padding:0.2rem 0;">
                    <span>{{ $document->title }} <span style="opacity:0.7; font-size:0.85rem;">{{ \LiteCrm\Filament\Support\Fields::recordLabel($document->documentable) }}</span></span>
                    <span style="font-size:0.8rem; {{ $document->isExpired() ? 'color:rgb(220 38 38);' : 'color:rgb(217 119 6);' }}">{{ $document->expires_on?->toFormattedDateString() }}</span>
                </li>
            @empty
                <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.expiring.none') }}</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
