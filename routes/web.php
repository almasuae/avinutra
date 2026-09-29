<?php

declare(strict_types=1);

use App\Content\Services;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
 * The public website (Content Blueprint v3 §6 site map). Route names are used
 * by config/site.php: a navigation link appears only when its route exists.
 */
Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::view('/about/company', 'pages.about.company')->name('about.company');
Route::view('/about/editorial-policy', 'pages.about.editorial-policy')->name('about.editorial-policy');
Route::get('/about/team', [PageController::class, 'team'])->name('about.team');

Route::get('/nutrition-services', [PageController::class, 'services'])->name('services');
Route::view('/nutrition-services/feed-mills', 'pages.services.feed-mills')->name('services.feed-mills');
Route::view('/nutrition-services/request-sourcing', 'pages.services.request-sourcing')->name('services.request-sourcing');
Route::get('/nutrition-services/{service}', [PageController::class, 'service'])
    ->whereIn('service', Services::slugs())
    ->name('services.show');

Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients');
Route::view('/ingredients/amino-acids/methionine', 'pages.ingredients.methionine')->name('ingredients.methionine');
Route::get('/ingredients/{category}', [IngredientController::class, 'category'])->name('ingredients.category');
Route::get('/ingredients/{category}/{product}', [IngredientController::class, 'product'])->name('ingredients.product');

Route::view('/quality', 'pages.quality')->name('quality');

Route::view('/suppliers', 'pages.suppliers.index')->name('suppliers');
Route::view('/suppliers/how-we-work', 'pages.suppliers.how-we-work')->name('suppliers.how-we-work');
Route::view('/suppliers/apply', 'pages.suppliers.apply')->name('suppliers.apply');

Route::view('/tools', 'pages.tools.index')->name('tools');

Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge');
Route::get('/knowledge/glossary', [KnowledgeController::class, 'glossary'])->name('knowledge.glossary');
Route::get('/knowledge/{slug}', [KnowledgeController::class, 'show'])->name('knowledge.show');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/ask-a-nutritionist', [PageController::class, 'askNutritionist'])->name('ask-a-nutritionist');

Route::view('/legal/privacy', 'pages.legal.privacy')->name('legal.privacy');
Route::view('/legal/terms', 'pages.legal.terms')->name('legal.terms');
Route::view('/legal/cookies', 'pages.legal.cookies')->name('legal.cookies');
Route::view('/legal/technical-disclaimer', 'pages.legal.technical-disclaimer')->name('legal.technical-disclaimer');

/*
 * Local development only: a page with the CRM enquiry form, for testing the
 * enquiry flow, and the design review page (header, logo, components, colour
 * contrast). Never registered in production (APP_ENV=production), so it
 * cannot be reached or cached there.
 */
if (app()->environment('local')) {
    Route::view('/dev/enquiry-form', 'dev.enquiry-form')->name('dev.enquiry-form');
    Route::view('/dev/design', 'dev.design')->name('dev.design');
}
