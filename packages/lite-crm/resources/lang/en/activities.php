<?php

declare(strict_types=1);

return [
    'label' => 'activity',
    'plural' => 'Activities',
    'minutes' => 'min',
    'fields' => [
        'type' => 'Type',
        'occurred_at' => 'When',
        'duration' => 'Duration',
        'participant_users' => 'Team members present',
        'participant_contacts' => 'Contacts present',
        'summary' => 'Summary',
        'outcome' => 'Outcome',
        'next_step' => 'Next step',
        'logged_by' => 'Logged by',
    ],
    'actions' => [
        'log' => 'Log activity',
    ],
    'notifications' => [
        'logged' => 'Activity logged',
    ],
];
