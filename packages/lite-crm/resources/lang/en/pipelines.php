<?php

declare(strict_types=1);

return [
    'label' => 'pipeline',
    'plural' => 'Pipelines',
    'fields' => [
        'name' => 'Name',
        'key' => 'Key',
        'description' => 'Description',
        'sort' => 'Order',
        'is_active' => 'Active',
        'stages' => 'Stages',
        'stage_name' => 'Stage',
        'probability' => 'Probability',
        'is_won' => 'Won',
        'is_lost' => 'Lost',
        'visible_to_roles' => 'Only visible to these roles',
        'visible_to_roles_help' => 'Leave all unticked to show the pipeline to everyone who works with opportunities. Admins always see every pipeline.',
    ],
];
