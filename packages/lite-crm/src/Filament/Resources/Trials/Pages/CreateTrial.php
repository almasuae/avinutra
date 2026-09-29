<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Trials\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Trials\TrialResource;

class CreateTrial extends CreateRecord
{
    protected static string $resource = TrialResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
