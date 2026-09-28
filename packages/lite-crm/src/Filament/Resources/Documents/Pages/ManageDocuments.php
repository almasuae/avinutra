<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Documents\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\Documents\DocumentResource;

class ManageDocuments extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('lite-crm::documents.actions.upload')),
        ];
    }
}
