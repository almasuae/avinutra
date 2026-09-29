<?php

declare(strict_types=1);

namespace App\Filament\Resources\GlossaryTerms\Pages;

use App\Filament\Resources\GlossaryTerms\GlossaryTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\FormLayout;

class ManageGlossaryTerms extends ManageRecords
{
    protected static string $resource = GlossaryTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FormLayout::wide(CreateAction::make()),
        ];
    }
}
