<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Trials\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use LiteCrm\Filament\Resources\Trials\TrialResource;

class EditTrial extends EditRecord
{
    protected static string $resource = TrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
