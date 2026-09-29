<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamProfiles\Pages;

use App\Filament\Resources\TeamProfiles\TeamProfileResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

/**
 * Full page (CRM form layout): biography and publications get the width of the screen.
 */
class CreateTeamProfile extends CreateRecord
{
    protected static string $resource = TeamProfileResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
