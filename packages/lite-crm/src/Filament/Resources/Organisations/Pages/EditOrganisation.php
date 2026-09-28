<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;

class EditOrganisation extends EditRecord
{
    protected static string $resource = OrganisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
