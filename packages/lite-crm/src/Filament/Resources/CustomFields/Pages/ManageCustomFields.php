<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\CustomFields\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\CustomFields\CustomFieldResource;

class ManageCustomFields extends ManageRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FormLayout::wide(CreateAction::make()),
        ];
    }
}
