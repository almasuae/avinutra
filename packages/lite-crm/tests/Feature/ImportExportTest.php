<?php

declare(strict_types=1);

use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use LiteCrm\Filament\Resources\Contacts\Pages\ListContacts;
use LiteCrm\Filament\Resources\Organisations\Pages\ListOrganisations;
use LiteCrm\ImportExport\ContactImporter;
use LiteCrm\ImportExport\OrganisationExporter;
use LiteCrm\ImportExport\OrganisationImporter;
use LiteCrm\ImportExport\ProductImporter;
use LiteCrm\Models\Contact;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Product;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    $this->actingAs($this->withMfa($this->crmUser(['admin'])));
});

it('imports organisations with list values and custom fields', function (): void {
    CustomField::query()->create(['entity' => 'organisation', 'key' => 'capacity', 'label' => 'Capacity', 'type' => 'decimal']);
    $type = Lookup::query()->where('type', 'organisation_type')->firstOrFail();

    OrganisationImporter::test()
        ->import(['name' => 'Alpha Mills', 'city' => 'Lahore', 'type' => strtoupper($type->label), 'custom_capacity' => '12.5'])
        ->assertImported();

    $organisation = Organisation::query()->sole();

    expect($organisation->type_id)->toBe($type->getKey())
        ->and($organisation->custom['capacity'] ?? null)->toEqual(12.5);
});

it('never imports a duplicate organisation (same name and city)', function (): void {
    Organisation::query()->create(['name' => 'Alpha Mills', 'city' => 'Lahore']);

    OrganisationImporter::test()
        ->import(['name' => 'alpha mills', 'city' => 'lahore'])
        ->assertHasRowFailure(__('lite-crm::import.duplicate_organisation', ['name' => 'Alpha Mills']));

    OrganisationImporter::test()->import(['name' => 'Alpha Mills', 'city' => 'Multan'])->assertImported();

    expect(Organisation::query()->count())->toBe(2);
});

it('updates an existing organisation when asked to', function (): void {
    Organisation::query()->create(['name' => 'Alpha Mills', 'city' => 'Lahore']);

    OrganisationImporter::test(options: ['update_existing' => true])
        ->import(['name' => 'Alpha Mills', 'city' => 'Lahore', 'phone' => '+92 000'])
        ->assertImported();

    expect(Organisation::query()->count())->toBe(1)
        ->and(Organisation::query()->sole()->phone)->toBe('+92 000');
});

it('refuses to update a record the importing user may not change', function (): void {
    $other = $this->crmUser(['commercial']);
    Organisation::query()->create(['name' => 'Private Mill', 'city' => 'Lahore', 'owner_id' => $other->getKey()]);
    $this->actingAs($this->withMfa($this->crmUser(['viewer'])));

    OrganisationImporter::test(options: ['update_existing' => true])
        ->import(['name' => 'Private Mill', 'city' => 'Lahore', 'phone' => '1'])
        ->assertHasRowFailure();
});

it('imports contacts, matching duplicates by e-mail and linking the organisation', function (): void {
    Organisation::query()->create(['name' => 'Alpha Mills', 'city' => 'Lahore']);
    Contact::query()->create(['first_name' => 'Ayesha', 'last_name' => 'Khan', 'email' => 'ayesha@example.org']);

    ContactImporter::test()
        ->import(['first_name' => 'Ayesha', 'last_name' => 'Khan', 'email' => 'AYESHA@example.org'])
        ->assertHasRowFailure();

    ContactImporter::test()
        ->import(['first_name' => 'Bilal', 'last_name' => 'Ahmed', 'email' => 'bilal@example.org', 'organisation' => 'Alpha Mills'])
        ->assertImported();

    expect(Contact::query()->where('email', 'bilal@example.org')->sole()->organisation?->name)->toBe('Alpha Mills');
});

it('validates imported rows', function (): void {
    ContactImporter::test()
        ->import(['first_name' => 'Bad', 'last_name' => 'Row', 'email' => 'not-an-e-mail'])
        ->assertHasErrors(['email']);
});

it('imports products without publishing them or marking them available', function (): void {
    ProductImporter::test()->import(['name' => 'Additive X'])->assertImported();

    $product = Product::query()->sole();

    expect($product->publish_on_website)->toBeFalse();
});

it('shows import and export only to users allowed to use them', function (): void {
    Livewire::test(ListOrganisations::class)
        ->assertActionVisible('import')
        ->assertActionVisible('export');

    $this->actingAs($this->withMfa($this->crmUser(['partner'])));

    Livewire::test(ListContacts::class)
        ->assertActionHidden('import')
        ->assertActionHidden('export');
});

it('hides import and export when the module is switched off', function (): void {
    config(['lite-crm.modules.import_export' => false]);

    Livewire::test(ListOrganisations::class)
        ->assertActionHidden('import')
        ->assertActionHidden('export');
});

it('exports custom fields as columns', function (): void {
    CustomField::query()->create(['entity' => 'organisation', 'key' => 'capacity', 'label' => 'Capacity', 'type' => 'decimal']);

    $labels = array_map(fn ($column): string => (string) $column->getLabel(), OrganisationExporter::getColumns());

    expect($labels)->toContain('Capacity')
        ->and(OrganisationExporter::getCompletedNotificationBody(new Export(['successful_rows' => 3, 'total_rows' => 3])))->toContain('3');
});
