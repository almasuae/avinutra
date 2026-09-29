<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Enums\ProductAvailability;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Product;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->aminoAcids = Lookup::query()->where('type', 'product_category')->where('key', 'amino_acids')->sole();
});

function publishedProduct(array $attributes = []): Product
{
    return Product::query()->create([
        'name' => 'DL-Methionine 99%',
        'category_id' => test()->aminoAcids->getKey(),
        'description' => 'Feed-grade DL-Methionine.',
        'publish_on_website' => true,
        'availability' => ProductAvailability::Information,
        ...$attributes,
    ]);
}

it('lists only products marked "publish on website"', function (): void {
    publishedProduct();
    publishedProduct(['name' => 'Hidden Product', 'publish_on_website' => false]);

    $this->get('/ingredients')->assertOk()->assertSee('DL-Methionine 99%')->assertDontSee('Hidden Product');
    $this->get('/ingredients/amino-acids')->assertOk()->assertSee('DL-Methionine 99%');
    $this->get('/ingredients/amino-acids/hidden-product')->assertNotFound();
});

it('hides the product directory until a product is published', function (): void {
    $this->get('/ingredients')->assertOk()->assertDontSee('id="products"', false);
});

it('filters the directory by category, species, form and function', function (): void {
    $powder = publishedProduct(['name' => 'Powder Product']);
    $powder->setCustomValue('physical_form', 'powder')->setCustomValue('species', ['broiler'])->save();
    $liquid = publishedProduct(['name' => 'Liquid Product']);
    $liquid->setCustomValue('physical_form', 'liquid')->setCustomValue('species', ['layer'])->save();

    $this->get('/ingredients?physical_form=powder')->assertSee('Powder Product')->assertDontSee('Liquid Product');
    $this->get('/ingredients?species=layer')->assertSee('Liquid Product')->assertDontSee('Powder Product');
    $this->get('/ingredients?category=enzymes')->assertDontSee('Powder Product')->assertSee('No products match these filters.');
});

it('shows the availability wording and the matching calls to action', function (): void {
    $information = publishedProduct(['name' => 'Info Product']);
    $this->get('/ingredients/amino-acids/'.$information->slug)->assertOk()
        ->assertSee('Ask a Nutritionist')->assertDontSee('Request Quotation');

    $onRequest = publishedProduct(['name' => 'Sourced Product', 'availability' => ProductAvailability::OnRequest]);
    $this->get('/ingredients/amino-acids/'.$onRequest->slug)->assertSee('Sourced on request')->assertSee('Request Sourcing Support');

    $available = publishedProduct(['name' => 'Stocked Product', 'availability' => ProductAvailability::Available]);
    $this->get('/ingredients/amino-acids/'.$available->slug)
        ->assertSee('Available')->assertSee('Request Quotation')->assertSee('Request Sample')->assertSee('Request COA');
});

it('names the manufacturer only with recorded permission', function (): void {
    $product = publishedProduct();
    $maker = Organisation::query()->create(['name' => 'Example Manufacturing Co', 'city' => 'Shanghai']);
    $product->suppliers()->attach($maker);

    $this->get('/ingredients/amino-acids/'.$product->slug)
        ->assertSee('International manufacturer — details on request')->assertDontSee('Example Manufacturing Co');

    $maker->update(['permission_to_name_publicly' => true, 'permission_granted_on' => now()]);
    $this->get('/ingredients/amino-acids/'.$product->slug)->assertSee('Example Manufacturing Co');
});

it('shows certificates only when verified, public and in date', function (): void {
    $product = publishedProduct();
    // "verified" is not mass-assignable (it is set by Document::markVerified()), so it is forced here.
    $certificate = fn (string $title, array $extra = []) => Document::query()->make([
        'documentable_type' => $product->getMorphClass(), 'documentable_id' => $product->getKey(),
        'title' => $title, 'file_path' => 'x.pdf', 'file_name' => 'x.pdf', 'certificate_number' => 'C-1',
    ])->forceFill($extra)->save();

    $certificate('Unverified certificate');
    $certificate('Verified certificate', ['verified' => true, 'expires_on' => now()->addYear()]);
    $certificate('Expired certificate', ['verified' => true, 'expires_on' => now()->subDay()]);
    $certificate('Confidential certificate', ['verified' => true, 'confidential' => true]);

    $this->get('/ingredients/amino-acids/'.$product->slug)
        ->assertSee('Verified certificate')
        ->assertDontSee('Unverified certificate')
        ->assertDontSee('Expired certificate')
        ->assertDontSee('Confidential certificate');
});

it('shows the applications only after nutrition-panel review', function (): void {
    $product = publishedProduct();
    $product->setCustomValue('applications', 'Used in broiler diets.')->save();

    $this->get('/ingredients/amino-acids/'.$product->slug)->assertDontSee('Used in broiler diets.');

    $product->setCustomValue('applications_reviewed', true)->save();
    $this->get('/ingredients/amino-acids/'.$product->slug)->assertSee('Used in broiler diets.');
});

it('cites the verified EFSA opinions on the methionine guide', function (): void {
    $this->get('/ingredients/amino-acids/methionine')->assertOk()
        ->assertSee('10.2903/j.efsa.2012.2623', false)
        ->assertSee('10.2903/j.efsa.2018.5198', false)
        ->assertSee('2.708')->assertSee('4.169');
});
