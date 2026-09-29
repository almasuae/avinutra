<?php

declare(strict_types=1);

return [
    'label' => 'opportunity',
    'plural' => 'Opportunities',
    'stage_changed' => 'Stage changed: :from → :to',
    'sections' => [
        'deal' => 'Opportunity',
        'value' => 'Volume and value',
        'next' => 'Next step',
    ],
    'fields' => [
        'name' => 'Name',
        'pipeline' => 'Pipeline',
        'stage' => 'Stage',
        'lost_reason' => 'Lost reason',
        'volume' => 'Volume',
        'unit' => 'Unit',
        'value' => 'Value',
        'probability' => 'Probability',
        'probability_help' => 'Set from the stage when the stage changes; you can adjust it.',
        'weighted_value' => 'Weighted value',
        'expected_close_date' => 'Expected close',
        'closed_at' => 'Closed',
        'next_step' => 'Next step',
        'next_step_date' => 'Next step by',
        'notes' => 'Notes',
    ],
    'filters' => [
        'open' => 'Open',
    ],
    'actions' => [
        'mark_won' => 'Mark won',
        'mark_lost' => 'Mark lost',
    ],
    'errors' => [
        'stage_not_in_pipeline' => 'The stage does not belong to the chosen pipeline.',
    ],
    'board' => [
        'title' => 'Opportunity board',
        'navigation' => 'Board',
        'help' => 'Drag a card to another stage, or use its menu. Won and lost opportunities stay on the board for :days days.',
        'empty' => 'Nothing here yet.',
        'no_pipelines' => 'There is no pipeline you can work in.',
        'moved' => '":name" moved to :stage.',
        'not_allowed' => 'You cannot change this opportunity.',
        'move_to' => 'Move ":name" to another stage',
        'move_option' => 'Stage: :stage',
    ],
];
