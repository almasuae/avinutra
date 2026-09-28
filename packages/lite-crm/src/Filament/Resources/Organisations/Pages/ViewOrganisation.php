<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;

class ViewOrganisation extends ViewRecord
{
    protected static string $resource = OrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }
}
