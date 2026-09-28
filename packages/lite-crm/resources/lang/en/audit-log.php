<?php

declare(strict_types=1);

return [
    'label' => 'audit entry',
    'plural' => 'Audit log',
    'navigation' => 'Audit log',
    'system' => 'System',
    'fields' => [
        'created_at' => 'When',
        'causer' => 'Who',
        'event' => 'Event',
        'subject' => 'Record',
        'subject_type' => 'Record type',
        'log' => 'Log',
        'old' => 'Before',
        'new' => 'After',
        'from' => 'From',
        'until' => 'Until',
    ],
];
