<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.team_clock.heading')">
        <ul>
            @foreach ($people as $person)
                <li style="display:flex; justify-content:space-between; gap:0.5rem; padding:0.2rem 0;">
                    <span>{{ $person['name'] }} <span style="opacity:0.7; font-size:0.85rem;">{{ $person['city'] ?? $person['zone'] }}</span></span>
                    <span style="font-variant-numeric:tabular-nums;">{{ $person['time']->format('H:i') }} <span style="opacity:0.6; font-size:0.8rem;">{{ $person['time']->format('D') }}</span></span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
