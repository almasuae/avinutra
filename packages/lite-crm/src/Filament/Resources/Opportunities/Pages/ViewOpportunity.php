<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Opportunities\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\PipelineStage;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markWon')
                ->label(__('lite-crm::opportunities.actions.mark_won'))
                ->icon(Heroicon::OutlinedTrophy)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Opportunity $record): bool => $record->isOpen() && static::closingStage($record, won: true) !== null && Gate::allows('update', $record))
                ->action(fn (Opportunity $record) => $record->update(['stage_id' => static::closingStage($record, won: true)?->getKey()])),
            Action::make('markLost')
                ->label(__('lite-crm::opportunities.actions.mark_lost'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Opportunity $record): bool => $record->isOpen() && static::closingStage($record, won: false) !== null && Gate::allows('update', $record))
                ->schema([Fields::lookup('lost_reason_id', 'lost_reason', __('lite-crm::opportunities.fields.lost_reason'))->required()])
                ->action(fn (Opportunity $record, array $data) => $record->update([
                    'stage_id' => static::closingStage($record, won: false)?->getKey(),
                    'lost_reason_id' => $data['lost_reason_id'],
                ])),
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }

    /**
     * The pipeline's first "won" (or "lost") stage.
     */
    public static function closingStage(Opportunity $opportunity, bool $won): ?PipelineStage
    {
        /** @var PipelineStage|null $stage */
        $stage = LiteCrm::model(PipelineStage::class)::query()
            ->where('pipeline_id', $opportunity->pipeline_id)
            ->where($won ? 'is_won' : 'is_lost', true)
            ->orderBy('sort')
            ->first();

        return $stage;
    }
}
