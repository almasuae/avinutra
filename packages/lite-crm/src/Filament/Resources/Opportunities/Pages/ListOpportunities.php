<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Opportunities\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use LiteCrm\Filament\Pages\OpportunityBoard;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\ImportExport\CrmExporters;
use LiteCrm\ImportExport\OpportunityExporter;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label(__('lite-crm::opportunities.board.title'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(fn (): string => OpportunityBoard::getUrl()),
            CrmExporters::exportAction(OpportunityExporter::class, 'opportunities'),
            CreateAction::make(),
        ];
    }
}
