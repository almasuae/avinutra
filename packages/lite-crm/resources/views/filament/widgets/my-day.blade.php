<x-filament-widgets::widget style="height:100%">
    <x-filament::section style="height:100%" :heading="__('lite-crm::dashboard.my_day.heading')">
        <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::dashboard.my_day.tasks') }}</h3>
        <ul style="margin:0.4rem 0 1rem;">
            @forelse ($tasks as $task)
                <li style="display:flex; justify-content:space-between; gap:0.5rem; padding:0.2rem 0;">
                    <a href="{{ \LiteCrm\Filament\Resources\Tasks\TaskResource::getUrl() }}" style="text-decoration:underline;">{{ $task->title }}</a>
                    <span style="font-size:0.8rem; {{ $task->isOverdue() ? 'color:rgb(220 38 38);' : 'opacity:0.7;' }}">{{ $task->due_at?->toFormattedDayDateString() }}</span>
                </li>
            @empty
                <li style="opacity:0.6; font-size:0.9rem;">{{ __('lite-crm::dashboard.my_day.no_tasks') }}</li>
            @endforelse
        </ul>
        @if ($nextSteps->isNotEmpty())
            <h3 style="font-weight:600; font-size:0.9rem;">{{ __('lite-crm::dashboard.my_day.next_steps') }}</h3>
            <ul style="margin-top:0.4rem;">
                @foreach ($nextSteps as $opportunity)
                    <li style="display:flex; justify-content:space-between; gap:0.5rem; padding:0.2rem 0;">
                        <a href="{{ \LiteCrm\Filament\Resources\Opportunities\OpportunityResource::getUrl('view', ['record' => $opportunity]) }}" style="text-decoration:underline;">{{ $opportunity->next_step ?: $opportunity->name }}</a>
                        <span style="font-size:0.8rem; opacity:0.7;">{{ $opportunity->next_step_date?->toFormattedDayDateString() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
