<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamProfiles\Pages;

use App\Filament\Resources\TeamProfiles\TeamProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

/**
 * Full page (CRM form layout): biography and publications get the width of the screen.
 */
class EditTeamProfile extends EditRecord
{
    protected static string $resource = TeamProfileResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
