<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Product;

/**
 * Products from CSV, matched by name. Availability and website publication are
 * not imported: they are decided in the CRM by the people allowed to.
 */
class ProductImporter extends Importer
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->label(__('lite-crm::products.fields.name'))->requiredMapping()->rules(['required', 'max:255']),
            static::lookupImportColumn('category', 'category_id', 'product_category', __('lite-crm::products.fields.category')),
            ImportColumn::make('description')->label(__('lite-crm::products.fields.description'))->rules(['nullable', 'max:10000']),
            ImportColumn::make('packaging')->label(__('lite-crm::products.fields.packaging'))->rules(['nullable', 'max:255']),
            ImportColumn::make('storage')->label(__('lite-crm::products.fields.storage'))->rules(['nullable', 'max:255']),
            ImportColumn::make('shelf_life')->label(__('lite-crm::products.fields.shelf_life'))->rules(['nullable', 'max:255']),
            ...static::customImportColumns('product'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [Checkbox::make('update_existing')->label(__('lite-crm::import.update_existing'))];
    }

    public function resolveRecord(): ?Product
    {
        $name = Str::lower(trim((string) ($this->data['name'] ?? '')));

        /** @var Product|null $existing */
        $existing = LiteCrm::model(Product::class)::query()->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();

        if ($existing !== null) {
            if (! ($this->options['update_existing'] ?? false)) {
                throw new RowImportFailedException(__('lite-crm::import.duplicate_product', ['name' => $existing->name]));
            }

            if (Gate::denies('update', $existing)) {
                throw new RowImportFailedException(__('lite-crm::import.not_allowed_to_update'));
            }

            return $existing;
        }

        /** @var Product $product */
        $product = new (LiteCrm::model(Product::class));

        return $product;
    }
}
