<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.activity_stream.heading')">
        <ul>
            @forelse ($activities as $activity)
                <li style="display:flex; justify-content:space-between; gap:1rem; padding:0.3rem 0; border-bottom:1px solid rgba(125,125,125,0.15);">
                    <span>
                        <span style="font-weight:500;">{{ $activity->type?->label }}</span>
                        <span style="opacity:0.8;">{{ \Illuminate\Support\Str::limit($activity->summary, 120) }}</span>
                        <span style="opacity:0.6; font-size:0.85rem;">{{ \LiteCrm\Filament\Support\Fields::recordLabel($activity->subject) }}</span>
                    </span>
                    <span style="font-size:0.8rem; opacity:0.7; white-space:nowrap;">{{ $activity->owner?->name }} · {{ $activity->occurred_at->diffForHumans() }}</span>
                </li>
            @empty
                <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.activity_stream.none') }}</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
