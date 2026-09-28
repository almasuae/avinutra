<?php

declare(strict_types=1);

return [
    'task_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],
    'enquiry_status' => [
        'new' => 'New',
        'assigned' => 'Assigned',
        'in_progress' => 'In progress',
        'converted' => 'Converted',
        'closed' => 'Closed',
        'spam' => 'Spam',
    ],
    'task_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'preferred_channel' => [
        'email' => 'E-mail',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'in_person' => 'In person',
    ],
    'consent_basis' => [
        'consent' => 'Consent',
        'contract' => 'Contract',
        'legitimate_interest' => 'Legitimate interest',
        'other' => 'Other',
    ],
];
