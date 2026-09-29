<?php

declare(strict_types=1);

use LiteCrm\Models\Enquiry;
use LiteCrm\Support\Countries;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('lists ISO 3166-1 countries by name', function (): void {
    $countries = Countries::all();

    expect(count($countries))->toBeGreaterThan(240)->toBeLessThan(252)
        ->and($countries['DE'])->toBe('Germany')
        ->and($countries)->not->toHaveKey('EU')
        ->and($countries)->not->toHaveKey('ZZ')
        ->and(Countries::name('fr'))->toBe('France')
        ->and(Countries::name('XX'))->toBeNull();
});

it('stores the country as its ISO code and rejects anything else', function (): void {
    $form = Livewire::test('lite-crm.enquiry-form', [
        'type' => 'general',
        'fields' => ['name' => ['required' => true], 'email' => ['required' => true], 'country' => ['type' => 'country', 'required' => true], 'message' => ['required' => true]],
    ]);
    $this->travel(10)->seconds();

    $form->set('data', ['name' => 'A Buyer', 'email' => 'buyer@example.com', 'country' => 'Narnia', 'message' => 'Hi'])
        ->set('consent', true)
        ->call('submit')
        ->assertHasErrors(['data.country']);

    $form->set('data.country', 'DE')->call('submit')->assertHasNoErrors();

    expect(Enquiry::query()->sole()->country)->toBe('DE');
});

it('renders the country field as a list of countries without keeping it in the component state', function (): void {
    $form = Livewire::test('lite-crm.enquiry-form', ['fields' => ['country' => ['type' => 'country']]]);

    $form->assertSee('Germany');
    expect($form->get('definitions')['country'])->toBe(['label' => 'Country', 'type' => 'country', 'required' => false, 'options' => []]);
});
