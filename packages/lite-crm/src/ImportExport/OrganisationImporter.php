<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Enquiries\EnquiryConverter;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Organisation;

/**
 * Organisations from CSV. A row matching an existing organisation (same name
 * and city) updates it when "Update existing records" is ticked; otherwise it
 * is reported as a duplicate and skipped.
 */
class OrganisationImporter extends Importer
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Organisation::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->label(__('lite-crm::organisations.fields.name'))->requiredMapping()->rules(['required', 'max:255']),
            static::lookupImportColumn('type', 'type_id', 'organisation_type', __('lite-crm::organisations.fields.type')),
            static::lookupImportColumn('status', 'status_id', 'organisation_status', __('lite-crm::organisations.fields.status')),
            static::lookupImportColumn('territory', 'territory_id', 'territory', __('lite-crm::organisations.fields.territory')),
            ImportColumn::make('country')->label(__('lite-crm::organisations.fields.country'))->rules(['nullable', 'max:100']),
            ImportColumn::make('region')->label(__('lite-crm::organisations.fields.region'))->rules(['nullable', 'max:255']),
            ImportColumn::make('city')->label(__('lite-crm::organisations.fields.city'))->rules(['nullable', 'max:255']),
            ImportColumn::make('address')->label(__('lite-crm::organisations.fields.address'))->rules(['nullable', 'max:2000']),
            ImportColumn::make('website')->label(__('lite-crm::organisations.fields.website'))->rules(['nullable', 'url', 'max:255']),
            ImportColumn::make('phone')->label(__('lite-crm::organisations.fields.phone'))->rules(['nullable', 'max:50']),
            ImportColumn::make('email')->label(__('lite-crm::organisations.fields.email'))->rules(['nullable', 'email', 'max:255']),
            ImportColumn::make('source')->label(__('lite-crm::organisations.fields.source'))->rules(['nullable', 'max:255']),
            ImportColumn::make('notes')->label(__('lite-crm::organisations.fields.notes'))->rules(['nullable', 'max:10000']),
            ...static::customImportColumns('organisation'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [Checkbox::make('update_existing')->label(__('lite-crm::import.update_existing'))];
    }

    public function resolveRecord(): ?Organisation
    {
        $existing = app(EnquiryConverter::class)->duplicateOrganisation($this->data['name'] ?? null, $this->data['city'] ?? null);

        if ($existing !== null) {
            if (! ($this->options['update_existing'] ?? false)) {
                throw new RowImportFailedException(__('lite-crm::import.duplicate_organisation', ['name' => $existing->name]));
            }

            if (Gate::denies('update', $existing)) {
                throw new RowImportFailedException(__('lite-crm::import.not_allowed_to_update'));
            }

            return $existing;
        }

        /** @var Organisation $organisation */
        $organisation = new (LiteCrm::model(Organisation::class));

        return $organisation;
    }
}
