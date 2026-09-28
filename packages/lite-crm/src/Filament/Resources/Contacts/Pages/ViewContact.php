<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Contacts\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Contacts\ContactResource;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }
}
