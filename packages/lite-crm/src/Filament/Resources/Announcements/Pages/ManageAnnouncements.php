<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Announcements\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\Announcements\AnnouncementResource;

class ManageAnnouncements extends ManageRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FormLayout::wide(CreateAction::make()),
        ];
    }
}
