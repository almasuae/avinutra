<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Panel path
    |--------------------------------------------------------------------------
    |
    | The URL path of the Filament panel that hosts the CRM.
    |
    */

    'path' => env('LITE_CRM_PATH', 'crm'),

    /*
    |--------------------------------------------------------------------------
    | Table prefix
    |--------------------------------------------------------------------------
    |
    | Every table created by the package uses this prefix.
    |
    */

    'table_prefix' => 'crm_',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The user model of the host application (it must implement
    | LiteCrm\Contracts\CrmUser, e.g. with the InteractsWithCrm trait), and a
    | class map for extending package models: map a package model to a
    | subclass of it, e.g. LiteCrm\Models\Lookup::class => App\Models\Lookup::class.
    |
    */

    'user_model' => env('LITE_CRM_USER_MODEL', 'App\\Models\\User'),

    'models' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Each module can be switched off per site. Code must keep working when a
    | module is disabled.
    |
    */

    'modules' => [
        'organisations' => true,
        'contacts' => true,
        'enquiries' => true,
        'opportunities' => true,
        'activities' => true,
        'tasks' => true,
        'products' => true,
        'samples' => true,
        'trials' => true,
        'quotations' => true,
        'price_log' => true,
        'documents' => true,
        'announcements' => true,
        'decisions' => true,
        'dashboard' => true,
        'import_export' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currencies and territories
    |--------------------------------------------------------------------------
    */

    'currencies' => ['USD'],

    'base_currency' => 'USD',

    'territories' => [],

    /*
    |--------------------------------------------------------------------------
    | Enquiry intake endpoint
    |--------------------------------------------------------------------------
    |
    | An optional, token-protected HTTP endpoint so that other websites can
    | post enquiries into this CRM.
    |
    */

    'enquiry_api' => [
        'enabled' => (bool) env('LITE_CRM_ENQUIRY_API', false),
        'token' => env('LITE_CRM_ENQUIRY_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'digest_hour' => 8,
        'stale_opportunity_days' => 21,
        'expiry_warning_days' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication and access
    |--------------------------------------------------------------------------
    |
    | Login is throttled by Filament (5 attempts per minute). Sessions end a
    | fixed number of minutes after login, whatever the activity. Roles listed
    | in "mfa_required_roles" must set up app-based MFA; an Admin can extend
    | that to every user in CRM › Settings.
    |
    */

    'auth' => [
        'password_min_length' => 12,
        'session_lifetime_minutes' => 480,
        'invitation_expiry_hours' => 72,
        'super_admin_role' => 'admin',
        'mfa_required_roles' => ['admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    |
    | Files are kept on a private disk and served only through short-lived
    | signed links, after a permission check.
    |
    */

    'documents' => [
        'disk' => env('LITE_CRM_DOCUMENTS_DISK', 'local'),
        'directory' => 'crm/documents',
        'max_size_kb' => 10240,
        'accepted_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
        ],
        'download_link_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom fields
    |--------------------------------------------------------------------------
    |
    | The entities that accept custom fields. Values are stored in each
    | entity's "custom" JSON column.
    |
    */

    'custom_field_entities' => [
        'organisation',
        'contact',
        'opportunity',
        'product',
        'trial',
        'sample',
    ],

    // The lookup type that classifies each entity's records. A custom field can
    // be limited to some of these types ("visible_for_types").
    'custom_field_type_lookups' => [
        'organisation' => 'organisation_type',
        'product' => 'product_category',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup types
    |--------------------------------------------------------------------------
    |
    | Simple lists kept in the crm_lookups table and edited in CRM › Settings.
    | Pipelines (with stages) and tags have their own tables.
    |
    */

    'lookup_types' => [
        'organisation_type',
        'organisation_status',
        'activity_type',
        'document_type',
        'lost_reason',
        'territory',
        'enquiry_type',
        'product_category',
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party tables
    |--------------------------------------------------------------------------
    |
    | When true, the package stores roles, permissions and the audit log in
    | crm_-prefixed tables (crm_roles, crm_permissions, crm_activity_log ...).
    | Set to false if the host already uses spatie/laravel-permission or
    | spatie/laravel-activitylog with its own tables.
    |
    */

    'prefix_third_party_tables' => true,

];
