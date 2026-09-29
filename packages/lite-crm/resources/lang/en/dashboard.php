<?php

declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'total' => 'Total',
    'no_reason' => 'No reason given',
    'stale_rates' => 'Exchange rates older than :days days are in use: :rates. Update them in CRM settings › Exchange rates.',
    'missing_rates' => 'Some values are left out of the totals because their currency has no exchange rate yet (CRM settings › Exchange rates).',
    'filters' => [
        'user' => 'User',
        'everyone' => 'Everyone',
        'territory' => 'Territory',
        'all_territories' => 'All territories',
        'from' => 'From',
        'until' => 'Until',
    ],
    'my_day' => [
        'heading' => 'My day',
        'tasks' => 'Tasks due today or overdue',
        'no_tasks' => 'Nothing due. Well done.',
        'next_steps' => 'My next steps this week',
    ],
    'enquiries' => [
        'new' => 'New',
        'unassigned' => 'Unassigned',
        'average_response' => 'Average first response since :date',
        'none' => 'No enquiries yet.',
    ],
    'won_lost' => [
        'heading' => 'Won and lost',
        'won' => 'Won',
        'lost' => 'Lost',
    ],
    'activity_by_user' => [
        'heading' => 'Activities by user',
        'label' => 'Activities',
    ],
    'expiring' => [
        'heading' => 'Documents expiring within :days days',
        'none' => 'Nothing is about to expire.',
    ],
    'price_watch' => [
        'heading' => 'Price watch (monthly average, :currency)',
    ],
    'samples_trials' => [
        'heading' => 'Samples and trials in progress',
        'no_samples' => 'No samples awaiting feedback.',
        'no_trials' => 'No trials planned or running.',
    ],
    'team_clock' => [
        'heading' => 'Team clock',
    ],
    'notices' => [
        'heading' => 'Announcements and decisions',
        'none' => 'No pinned announcements or decisions yet.',
    ],
    'activity_stream' => [
        'heading' => 'Recent activity',
        'none' => 'No activity yet.',
    ],
];
