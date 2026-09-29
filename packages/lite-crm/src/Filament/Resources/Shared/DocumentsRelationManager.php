<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Shared;

use Filament\Actions\CreateAction;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\Documents\DocumentResource;

class DocumentsRelationManager extends ChildRecordsRelationManager
{
    protected static string $relationship = 'documents';

    protected static function childResource(): string
    {
        return DocumentResource::class;
    }

    protected function createAction(): CreateAction
    {
        return FormLayout::wide(CreateAction::make())->label(__('lite-crm::documents.actions.upload'));
    }
}
