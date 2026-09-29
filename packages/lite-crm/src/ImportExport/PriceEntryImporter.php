<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LiteCrm\Enums\PriceBasis;
use LiteCrm\Enums\PriceConfidence;
use LiteCrm\Enums\PriceSourceType;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\PriceEntry;
use LiteCrm\Models\Product;

/**
 * Price log entries from CSV. Product and source are matched by name.
 */
class PriceEntryImporter extends Importer
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = PriceEntry::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('observed_on')->label(__('lite-crm::price-log.fields.observed_on'))->requiredMapping()->rules(['required', 'date']),
            ImportColumn::make('product')
                ->label(__('lite-crm::products.label'))
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing(function (Model $record, mixed $state): void {
                    $id = LiteCrm::model(Product::class)::query()->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim((string) $state))])->value('id');

                    if ($id === null) {
                        throw new RowImportFailedException(__('lite-crm::import.product_not_found', ['name' => $state]));
                    }

                    $record->setAttribute('product_id', $id);
                }),
            ImportColumn::make('source_type')
                ->label(__('lite-crm::price-log.fields.source_type'))
                ->requiredMapping()
                ->rules(['required', Rule::enum(PriceSourceType::class)]),
            ImportColumn::make('source_organisation')
                ->label(__('lite-crm::price-log.fields.source_organisation'))
                ->fillRecordUsing(function (Model $record, mixed $state): void {
                    if (filled($state)) {
                        $record->setAttribute('source_organisation_id', LiteCrm::model(Organisation::class)::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim((string) $state))])
                            ->value('id'));
                    }
                }),
            ImportColumn::make('basis')->label(__('lite-crm::price-log.fields.basis'))->requiredMapping()->rules(['required', Rule::enum(PriceBasis::class)]),
            ImportColumn::make('location')->label(__('lite-crm::price-log.fields.location'))->rules(['nullable', 'max:255']),
            ImportColumn::make('price')->label(__('lite-crm::price-log.fields.price'))->requiredMapping()->numeric()->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('currency')->label(__('lite-crm::common.fields.currency'))->requiredMapping()
                ->castStateUsing(fn (mixed $state): string => strtoupper(trim((string) $state)))
                ->rules(fn (): array => ['required', Rule::in(LiteCrm::currencies())]),
            ImportColumn::make('unit')->label(__('lite-crm::price-log.fields.unit'))->requiredMapping()->rules(['required', 'max:30']),
            ImportColumn::make('valid_until')->label(__('lite-crm::price-log.fields.valid_until'))->rules(['nullable', 'date']),
            ImportColumn::make('reference')->label(__('lite-crm::price-log.fields.reference'))->rules(['nullable', 'max:255']),
            ImportColumn::make('confidence')->label(__('lite-crm::price-log.fields.confidence'))->rules(['nullable', Rule::enum(PriceConfidence::class)]),
            ImportColumn::make('notes')->label(__('lite-crm::price-log.fields.notes'))->rules(['nullable', 'max:10000']),
        ];
    }

    public function resolveRecord(): ?PriceEntry
    {
        /** @var PriceEntry $entry */
        $entry = new (LiteCrm::model(PriceEntry::class));

        return $entry;
    }
}
