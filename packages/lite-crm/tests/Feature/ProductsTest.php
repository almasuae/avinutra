<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use LiteCrm\Enums\ProductAvailability;
use LiteCrm\Filament\Resources\Products\Pages\CreateProduct;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Product;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

it('creates unique web slugs from the name', function (): void {
    $a = Product::query()->create(['name' => 'Widget Plus']);
    $b = Product::query()->create(['name' => 'Widget Plus']);

    expect($a->slug)->toBe('widget-plus')
        ->and($b->slug)->toBe('widget-plus-2')
        ->and($a->availability)->toBe(ProductAvailability::Information)
        ->and($a->publish_on_website)->toBeFalse();
});

it('lets only authorised users mark a product available or publish it', function (): void {
    $this->actingAs($this->crmUser(['specialist']));
    $product = Product::query()->create(['name' => 'Widget']);

    expect(fn () => $product->update(['availability' => ProductAvailability::Available]))->toThrow(AuthorizationException::class)
        ->and(fn () => $product->refresh()->update(['publish_on_website' => true]))->toThrow(AuthorizationException::class)
        ->and($product->refresh()->availability)->toBe(ProductAvailability::Information);

    $product->update(['availability' => ProductAvailability::OnRequest]);
    expect($product->refresh()->availability)->toBe(ProductAvailability::OnRequest);

    $this->actingAs($this->crmUser(['manager']));
    $product->update(['availability' => ProductAvailability::Available, 'publish_on_website' => true]);

    expect($product->refresh()->availability)->toBe(ProductAvailability::Available)
        ->and($product->publish_on_website)->toBeTrue();
});

it('links suppliers with notes', function (): void {
    $product = Product::query()->create(['name' => 'Widget']);
    $supplier = Organisation::query()->create(['name' => 'Maker Ltd']);

    $product->suppliers()->attach($supplier, ['notes' => 'Main source']);

    expect($product->suppliers()->first()->pivot->notes)->toBe('Main source');
});

it('stores a specification table and custom fields by category', function (): void {
    $category = Lookup::query()->create(['type' => 'product_category', 'key' => 'widgets', 'label' => 'Widgets']);

    $product = Product::query()->create([
        'name' => 'Widget',
        'category_id' => $category->id,
        'specification' => [['parameter' => 'Purity', 'value' => '99', 'unit' => '%']],
    ]);

    expect($product->refresh()->specification)->toBe([['parameter' => 'Purity', 'value' => '99', 'unit' => '%']])
        ->and($product->customFieldTypeKey())->toBe('widgets');
});

it('creates a product from the form, without the "available" option for non-managers', function (): void {
    $this->actingAs($this->crmUser(['specialist']));
    $this->withMfa(auth()->user());

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Widget', 'availability' => 'on_request'])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Gadget', 'availability' => 'available'])
        ->call('create')
        ->assertHasFormErrors(['availability']);

    expect(Product::query()->pluck('name')->all())->toBe(['Widget']);
});
