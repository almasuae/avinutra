<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::enquiries.plural')">
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem; margin-bottom:0.8rem;">
            <div>
                <div style="font-size:1.6rem; font-weight:600;">{{ $new }}</div>
                <div style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::dashboard.enquiries.new') }}</div>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:600;">{{ $unassigned }}</div>
                <div style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::dashboard.enquiries.unassigned') }}</div>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:600;">{{ $averageHours === null ? '—' : $averageHours.' h' }}</div>
                <div style="font-size:0.8rem; opacity:0.7;">{{ __('lite-crm::dashboard.enquiries.average_response', ['date' => $from->toFormattedDateString()]) }}</div>
            </div>
        </div>
        <ul>
            @forelse ($latest as $enquiry)
                <li style="display:flex; justify-content:space-between; gap:0.5rem; padding:0.2rem 0;">
                    <a href="{{ \LiteCrm\Filament\Resources\Enquiries\EnquiryResource::getUrl('view', ['record' => $enquiry]) }}" style="text-decoration:underline;">{{ $enquiry->displayName() }}</a>
                    <span style="font-size:0.8rem; opacity:0.7;">{{ $enquiry->type?->label }} · {{ $enquiry->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.enquiries.none') }}</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
