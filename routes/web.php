<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

/*
 * Local development only: a page with the CRM enquiry form, for testing the
 * enquiry flow before the public pages exist (Phase 7), and the design review
 * page (fonts, header, footer options, logo, colour contrast). Never registered in
 * production (APP_ENV=production), so it cannot be reached or cached there.
 */
if (app()->environment('local')) {
    Route::view('/dev/enquiry-form', 'dev.enquiry-form')->name('dev.enquiry-form');
    Route::view('/dev/design', 'dev.design')->name('dev.design');
}
