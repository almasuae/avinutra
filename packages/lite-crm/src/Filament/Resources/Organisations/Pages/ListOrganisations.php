<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;
use LiteCrm\ImportExport\CrmExporters;
use LiteCrm\ImportExport\OrganisationExporter;
use LiteCrm\ImportExport\OrganisationImporter;
use LiteCrm\Models\Organisation;

class ListOrganisations extends ListRecords
{
    protected static string $resource = OrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CrmExporters::importAction(OrganisationImporter::class, Organisation::class),
            CrmExporters::exportAction(OrganisationExporter::class, 'organisations'),
            CreateAction::make(),
        ];
    }
}
