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
    | Sidebar navigation
    |--------------------------------------------------------------------------
    |
    | Record screens are grouped in the sidebar, in this order. Items are module
    | keys (see "modules"), plus "board" for the opportunity board. A group's
    | label is taken from "label" or, when null, from the translation
    | lite-crm::lite-crm.navigation.groups.{key}. Screens not listed here go into
    | the plugin's default group. The dashboard always stays at the top.
    |
    */

    'navigation' => [
        'groups' => [
            'sales' => [
                'label' => null,
                'items' => ['enquiries', 'organisations', 'contacts', 'opportunities', 'board', 'quotations'],
            ],
            'operations' => [
                'label' => null,
                'items' => ['products', 'samples', 'trials', 'documents', 'price_log'],
            ],
            'team' => [
                'label' => null,
                'items' => ['tasks', 'activities', 'announcements', 'decisions'],
            ],
        ],
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

    /*
    | Form defaults for long text (LiteCrm\Filament\FormLayout): textareas start at
    | 4 rows and grow with their content; Markdown editors are at least 400 px
    | high and full width. They apply to every Filament form in the application.
    */
    'forms' => [
        'defaults' => true,
    ],

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
    | An optional HTTP endpoint (POST /crm-api/enquiries) so that other websites
    | can post enquiries into this CRM. OFF by default. When on, callers need
    | the token generated in CRM › Settings (only its hash is stored; rotate it
    | there), and requests are rate-limited per IP.
    |
    */

    'enquiry_api' => [
        'enabled' => (bool) env('LITE_CRM_ENQUIRY_API', false),
        'path' => 'crm-api/enquiries',
        'requests_per_minute' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enquiries
    |--------------------------------------------------------------------------
    |
    | Spam protection without external services: a honeypot field, a minimum
    | time to fill the form, and a per-IP limit. Honeypot and timing failures
    | are stored with the Spam status for review; submissions over the limit are
    | refused. New enquiries are e-mailed to the roles below and to the mailbox
    | set on the enquiry type (meta "mailbox"). The acknowledgement mentions a
    | response time only when one is set, as a phrase such as "within one
    | working day" ("We aim to reply :time."). Hosts can supply it from their
    | own settings with LiteCrm::resolveEnquiryResponseTimeUsing().
    |
    */

    'enquiries' => [
        'min_fill_seconds' => 3,
        'rate_limit' => [
            'attempts' => 5,
            'decay_seconds' => 600,
        ],
        'notify_roles' => ['admin', 'manager'],
        'acknowledge' => true,
        'response_time' => null,
        'max_uploads' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'digest_hour' => (int) env('LITE_CRM_DIGEST_HOUR', 8),
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
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Switch individual widgets off per site. "pipelines" is how many
    | pipelines the pipeline widget summarises.
    |
    */

    'dashboard_widgets' => [
        'my_day' => true,
        'enquiries' => true,
        'pipelines' => true,
        'won_lost' => true,
        'activity_by_user' => true,
        'expiring_documents' => true,
        'price_watch' => true,
        'samples_trials' => true,
        'team_clock' => true,
        'notices' => true,
        'activity_stream' => true,
    ],

    'dashboard' => [
        'pipelines' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quotations
    |--------------------------------------------------------------------------
    |
    | Numbers look like "Q-2026-0001" (prefix, year, zero-padded counter); they
    | restart each year and are never reused. The contracting entity comes from
    | the host's resolver (LiteCrm::resolveContractingEntityUsing) or this value.
    |
    */

    'quotations' => [
        'number_prefix' => 'Q',
        'number_format' => '{prefix}-{year}-{number}',
        'number_padding' => 4,
        'contracting_entity' => null,
        'default_validity_days' => 30,
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

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Extra folders searched by lite-crm:preset, besides the package's own
    | presets/ folder. Each preset is a PHP file returning an array.
    |
    */

    'preset_paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Exchange rates
    |--------------------------------------------------------------------------
    |
    | Rates are entered by hand. The pipeline widget and the weekly report warn
    | when a rate in use is older than this many days.
    |
    */

    'exchange_rates' => [
        'stale_after_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit log retention
    |--------------------------------------------------------------------------
    |
    | Audit entries older than this many days are removed by the daily
    | scheduled activitylog:clean run.
    |
    */

    'audit_log_days' => (int) env('LITE_CRM_AUDIT_LOG_DAYS', 730),

];
