<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;

class ListOrganisations extends ListRecords
{
    protected static string $resource = OrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
