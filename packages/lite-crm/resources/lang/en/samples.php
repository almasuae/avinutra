<?php

declare(strict_types=1);

return [
    'label' => 'sample',
    'plural' => 'Samples',
    'fields' => [
        'supplier' => 'Supplier',
        'lot_number' => 'Lot number',
        'quantity' => 'Quantity',
        'sent_on' => 'Sent on',
        'courier' => 'Courier',
        'tracking' => 'Tracking number',
        'received_on' => 'Received on',
        'feedback' => 'Feedback',
        'stage' => 'Progress',
    ],
    'stages' => [
        'preparing' => 'Preparing',
        'sent' => 'Sent',
        'received' => 'Received',
        'feedback' => 'Feedback received',
    ],
    'filters' => [
        'in_progress' => 'Awaiting feedback',
    ],
];
