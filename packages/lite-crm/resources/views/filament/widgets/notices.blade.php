<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.notices.heading')">
        @foreach ($announcements as $announcement)
            <article style="margin-bottom:0.8rem;">
                <h3 style="font-weight:600;">{{ $announcement->title }}</h3>
                <div style="font-size:0.9rem;" class="fi-prose">{{ $announcement->bodyHtml() }}</div>
            </article>
        @endforeach
        @if ($decisions->isNotEmpty())
            <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::decisions.plural') }}</h3>
            <ul style="margin-top:0.3rem;">
                @foreach ($decisions as $decision)
                    <li style="display:flex; justify-content:space-between; gap:0.5rem;">
                        <span>{{ $decision->title }}</span>
                        <span style="font-size:0.8rem; opacity:0.7;">{{ $decision->decided_on->toFormattedDateString() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($announcements->isEmpty() && $decisions->isEmpty())
            <p style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.notices.none') }}</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
