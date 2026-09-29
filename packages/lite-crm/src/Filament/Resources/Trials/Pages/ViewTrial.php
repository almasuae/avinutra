<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Trials\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Trials\TrialResource;

class ViewTrial extends ViewRecord
{
    protected static string $resource = TrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }
}
