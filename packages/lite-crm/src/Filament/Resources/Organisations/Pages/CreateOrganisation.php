<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;

class CreateOrganisation extends CreateRecord
{
    protected static string $resource = OrganisationResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
