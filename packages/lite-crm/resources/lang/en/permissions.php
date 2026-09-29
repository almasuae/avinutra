<?php

declare(strict_types=1);

/*
 * Readable permission labels: "{area}: {ability}", e.g. "Documents: view confidential"
 * for documents.view_confidential. The stored permission keys never change.
 */
return [
    'areas' => [
        'activities' => 'Activities',
        'announcements' => 'Announcements',
        'audit_log' => 'Audit log',
        'contacts' => 'Contacts',
        'custom_fields' => 'Custom fields',
        'dashboard' => 'Dashboard',
        'decisions' => 'Decisions',
        'documents' => 'Documents',
        'enquiries' => 'Enquiries',
        'import' => 'Import',
        'lookups' => 'Lists',
        'opportunities' => 'Opportunities',
        'organisations' => 'Organisations',
        'price_log' => 'Price log',
        'products' => 'Products',
        'quotations' => 'Quotations',
        'roles' => 'Roles',
        'samples' => 'Samples',
        'settings' => 'Settings',
        'tasks' => 'Tasks',
        'technical_content' => 'Technical content',
        'trials' => 'Trials',
        'users' => 'Users',
        'website' => 'Website',
    ],
    'abilities' => [
        'view' => 'view',
        'view_all' => 'view all',
        'create' => 'create',
        'update' => 'edit',
        'delete' => 'delete',
        'export' => 'export',
        'manage' => 'manage',
        'run' => 'run',
        'approve' => 'approve',
        'filter' => 'filter',
        'view_confidential' => 'view confidential',
        'mark_available' => 'mark available',
    ],
    'picker' => [
        'search' => 'Search permissions',
        'select_all' => 'Select all',
        'deselect_all' => 'Deselect all',
        'none_found' => 'No permission matches your search.',
        'selected' => ':count of :total selected',
    ],
];
