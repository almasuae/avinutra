<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Opportunities\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
