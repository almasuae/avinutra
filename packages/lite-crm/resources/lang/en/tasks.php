<?php

declare(strict_types=1);

return [
    'label' => 'task',
    'plural' => 'Tasks',
    'fields' => [
        'title' => 'Task',
        'description' => 'Description',
        'assignee' => 'Assigned to',
        'due_at' => 'Due',
        'priority' => 'Priority',
        'status' => 'Status',
    ],
    'tabs' => [
        'mine' => 'My tasks',
        'team' => 'Team',
        'overdue' => 'Overdue',
        'all' => 'All',
    ],
    'actions' => [
        'complete' => 'Mark done',
    ],
    'mail' => [
        'subject' => 'Task assigned to you: :title',
        'intro' => 'A task has been assigned to you: ":title".',
        'due' => 'Due: :date (your time).',
        'action' => 'Open your tasks',
    ],
];
