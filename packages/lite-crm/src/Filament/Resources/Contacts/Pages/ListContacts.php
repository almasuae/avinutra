<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Contacts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Contacts\ContactResource;
use LiteCrm\ImportExport\ContactExporter;
use LiteCrm\ImportExport\ContactImporter;
use LiteCrm\ImportExport\CrmExporters;
use LiteCrm\Models\Contact;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CrmExporters::importAction(ContactImporter::class, Contact::class),
            CrmExporters::exportAction(ContactExporter::class, 'contacts'),
            CreateAction::make(),
        ];
    }
}
