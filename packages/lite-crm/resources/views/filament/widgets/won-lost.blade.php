<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.won_lost.heading')" :description="$from->toFormattedDateString().' – '.$until->toFormattedDateString()">
        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.75rem; margin-bottom:0.8rem;">
            <div>
                <div style="font-size:1.6rem; font-weight:600; color:rgb(22 163 74);">{{ $won['count'] }}</div>
                <div style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::dashboard.won_lost.won') }} · {{ $base }} {{ number_format($won['value'], 0) }}</div>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:600; color:rgb(220 38 38);">{{ $lost['count'] }}</div>
                <div style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::dashboard.won_lost.lost') }} · {{ $base }} {{ number_format($lost['value'], 0) }}</div>
            </div>
        </div>
        @if ($reasons !== [])
            <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::opportunities.fields.lost_reason') }}</h3>
            <ul style="margin-top:0.3rem;">
                @foreach ($reasons as $reason => $count)
                    <li style="display:flex; justify-content:space-between;"><span>{{ $reason }}</span><span>{{ $count }}</span></li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
