<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LiteCrm\Http\Controllers\EnquiryApiController;

/*
 * Stateless intake endpoint (no session, no CSRF): authenticated by token.
 * The controller returns 404 while the endpoint is switched off.
 */
Route::post((string) config('lite-crm.enquiry_api.path', 'crm-api/enquiries'), EnquiryApiController::class)
    ->middleware('throttle:lite-crm-enquiry-api')
    ->name('lite-crm.enquiries.api');
