<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Contacts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Contacts\ContactResource;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
