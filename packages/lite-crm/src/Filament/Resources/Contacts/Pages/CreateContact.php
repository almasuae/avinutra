<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Contacts\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Contacts\ContactResource;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
