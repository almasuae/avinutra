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
    | The user model of the host application, and a class map of the package
    | models. A host can extend any package model and register its own class
    | here. Package models are added to this map as the modules are built.
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

];
