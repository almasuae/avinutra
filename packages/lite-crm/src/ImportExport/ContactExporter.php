<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\Models\Contact;

class ContactExporter extends Exporter
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')->label(__('lite-crm::contacts.fields.first_name')),
            ExportColumn::make('last_name')->label(__('lite-crm::contacts.fields.last_name')),
            ExportColumn::make('organisation.name')->label(__('lite-crm::contacts.fields.organisation')),
            ExportColumn::make('job_title')->label(__('lite-crm::contacts.fields.job_title')),
            ExportColumn::make('department')->label(__('lite-crm::contacts.fields.department')),
            ExportColumn::make('email')->label(__('lite-crm::contacts.fields.email')),
            ExportColumn::make('phone')->label(__('lite-crm::contacts.fields.phone')),
            ExportColumn::make('whatsapp')->label(__('lite-crm::contacts.fields.whatsapp')),
            ExportColumn::make('languages')->label(__('lite-crm::contacts.fields.languages'))->listAsJson(false),
            ExportColumn::make('time_zone')->label(__('lite-crm::contacts.fields.time_zone')),
            ExportColumn::make('consent_basis')->label(__('lite-crm::contacts.fields.consent_basis'))
                ->formatStateUsing(fn (mixed $state): string => $state instanceof HasLabel ? (string) $state->getLabel() : (string) $state),
            ExportColumn::make('consent_date')->label(__('lite-crm::contacts.fields.consent_date')),
            ...static::customExportColumns('contact'),
        ];
    }
}
