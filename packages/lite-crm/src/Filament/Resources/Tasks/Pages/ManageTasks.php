<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Tasks\Pages;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\Tasks\TaskResource;

/**
 * Tasks in three views: My tasks · Team · Overdue (and all).
 */
class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FormLayout::wide(CreateAction::make()),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'mine' => Tab::make(__('lite-crm::tasks.tabs.mine'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->scopes(['open'])->where('assignee_id', Filament::auth()->id())),
            'team' => Tab::make(__('lite-crm::tasks.tabs.team'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->scopes(['open'])),
            'overdue' => Tab::make(__('lite-crm::tasks.tabs.overdue'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->scopes(['overdue'])),
            'all' => Tab::make(__('lite-crm::tasks.tabs.all')),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'mine';
    }
}
