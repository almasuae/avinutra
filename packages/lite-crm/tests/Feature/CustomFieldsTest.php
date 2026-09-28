<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Validation\ValidationException;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldType;
use LiteCrm\Models\CustomField;
use LiteCrm\Tests\Fixtures\Account;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'employees', 'label' => 'Employees', 'type' => 'number', 'sort' => 10,
    ]);
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'segment', 'label' => 'Segment', 'type' => 'select', 'sort' => 20,
        'options' => [['value' => 'small', 'label' => 'Small'], ['value' => 'large', 'label' => 'Large']],
        'filterable' => true, 'show_in_table' => true,
    ]);
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'regions', 'label' => 'Regions', 'type' => 'multiselect', 'section' => 'Coverage',
        'options' => [['value' => 'north', 'label' => 'North'], ['value' => 'south', 'label' => 'South']],
        'filterable' => true,
    ]);
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'certified', 'label' => 'Certified', 'type' => 'boolean', 'filterable' => true,
    ]);
    // Required only for customers.
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'account_code', 'label' => 'Account code', 'type' => 'text',
        'required' => true, 'visible_for_types' => ['customer'],
    ]);
    // Another entity must not leak in.
    CustomField::query()->create(['entity' => 'contact', 'key' => 'nickname', 'label' => 'Nickname', 'type' => 'text']);
});

it('stores valid values in the custom JSON column, cast to their types', function (): void {
    $account = Account::query()->create([
        'name' => 'Acme', 'type_key' => 'supplier',
        'custom' => ['employees' => '250', 'segment' => 'large', 'regions' => ['north'], 'certified' => '1'],
    ]);

    expect($account->refresh()->custom)->toBe([
        'employees' => 250, 'segment' => 'large', 'regions' => ['north'], 'certified' => true,
    ])->and($account->getCustomValue('segment'))->toBe('large');
});

it('rejects values that do not match the definition', function (array $custom): void {
    expect(fn () => Account::query()->create(['name' => 'Acme', 'type_key' => 'supplier', 'custom' => $custom]))
        ->toThrow(ValidationException::class);
})->with([
    'not a number' => [['employees' => 'many']],
    'unknown choice' => [['segment' => 'medium']],
    'unknown multi choice' => [['regions' => ['east']]],
]);

it('applies required fields only to the record types they are for', function (): void {
    expect(fn () => Account::query()->create(['name' => 'Customer Ltd', 'type_key' => 'customer', 'custom' => []]))
        ->toThrow(ValidationException::class);

    $supplier = Account::query()->create(['name' => 'Supplier Ltd', 'type_key' => 'supplier', 'custom' => []]);

    expect($supplier->exists)->toBeTrue();
});

it('keeps values of fields that were switched off', function (): void {
    $account = Account::query()->create(['name' => 'Acme', 'type_key' => 'supplier', 'custom' => ['employees' => 5]]);

    CustomField::query()->where('key', 'employees')->update(['is_active' => false]);
    app(CustomFieldRegistry::class)->flush();

    $account->setCustomValue('segment', 'small')->save();

    expect($account->refresh()->custom)->toMatchArray(['employees' => 5, 'segment' => 'small']);
});

it('refreshes definitions when a field is saved', function (): void {
    expect(app(CustomFieldRegistry::class)->for('contact'))->toHaveCount(1);

    CustomField::query()->create(['entity' => 'contact', 'key' => 'hobby', 'label' => 'Hobby', 'type' => 'text']);

    expect(app(CustomFieldRegistry::class)->for('contact'))->toHaveCount(2);
});

it('filters records by custom values with portable JSON queries', function (): void {
    Account::query()->create(['name' => 'A', 'type_key' => 'supplier', 'custom' => ['segment' => 'large', 'regions' => ['north', 'south'], 'certified' => true]]);
    Account::query()->create(['name' => 'B', 'type_key' => 'supplier', 'custom' => ['segment' => 'small', 'regions' => ['south'], 'certified' => false]]);
    Account::query()->create(['name' => 'C', 'type_key' => 'supplier', 'custom' => []]);

    expect(Account::query()->where('custom->segment', 'large')->pluck('name')->all())->toBe(['A'])
        ->and(Account::query()->whereJsonContains('custom->regions', 'north')->pluck('name')->all())->toBe(['A'])
        ->and(Account::query()->whereJsonContains('custom->regions', 'south')->orderBy('name')->pluck('name')->all())->toBe(['A', 'B'])
        ->and(Account::query()->where('custom->certified', true)->pluck('name')->all())->toBe(['A'])
        ->and(Account::query()->where(fn ($q) => $q->whereNull('custom->certified')->orWhere('custom->certified', false))->orderBy('name')->pluck('name')->all())->toBe(['B', 'C']);
});

it('builds form sections and fields from the definitions', function (): void {
    $sections = CustomFieldComponents::form('organisation');

    expect($sections)->toHaveCount(2)
        ->each->toBeInstanceOf(Section::class);

    $fields = collect(CustomFieldRegistry::class)->pipe(fn () => app(CustomFieldRegistry::class)->for('organisation'))
        ->mapWithKeys(fn (CustomField $field) => [$field->key => CustomFieldComponents::field($field)]);

    expect($fields['employees'])->toBeInstanceOf(TextInput::class)
        ->and($fields['employees']->getName())->toBe('custom.employees')
        ->and($fields['segment'])->toBeInstanceOf(Select::class)
        ->and($fields['regions']->isMultiple())->toBeTrue()
        ->and($fields['certified'])->toBeInstanceOf(Toggle::class)
        ->and($fields->keys()->all())->not->toContain('nickname');
});

it('builds table columns and filters from the definitions', function (): void {
    $columns = CustomFieldComponents::tableColumns('organisation');
    $filters = CustomFieldComponents::tableFilters('organisation');

    expect($columns)->toHaveCount(1)
        ->and($columns[0]->getName())->toBe('custom.segment')
        ->and($filters)->toHaveCount(3)
        ->and(collect($filters)->map(fn ($filter): string => $filter::class)->countBy()->all())
        ->toEqual([TernaryFilter::class => 1, SelectFilter::class => 2]);
});

it('lists every field type with a label', function (): void {
    expect(CustomFieldType::options())->toHaveCount(11)
        ->and(CustomFieldType::options())->not->toContain('');
});
