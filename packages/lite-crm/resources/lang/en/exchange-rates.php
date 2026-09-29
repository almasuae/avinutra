<?php

declare(strict_types=1);

return [
    'label' => 'exchange rate',
    'plural' => 'Exchange rates',
    'fields' => [
        'currency' => 'Currency',
        'rate' => ':base for 1 :currency',
        'rate_column' => 'Rate (in :base)',
        'valid_from' => 'Valid from',
        'source' => 'Source',
        'source_help' => 'Where the rate comes from, e.g. the central bank\'s published rate and its date.',
    ],
];
