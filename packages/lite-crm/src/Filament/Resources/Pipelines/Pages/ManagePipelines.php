<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Pipelines\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\Pipelines\PipelineResource;

class ManagePipelines extends ManageRecords
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FormLayout::wide(CreateAction::make()),
        ];
    }
}
