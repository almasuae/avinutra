<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Actions;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\Resources\Activities\ActivityResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Activity;
use Livewire\Component;
use LogicException;

/**
 * "Log activity" for a record page header: records a call, meeting, e-mail ...
 * against the page's record.
 */
class LogActivityAction
{
    public static function make(): CreateAction
    {
        return CreateAction::make('logActivity')
            ->label(__('lite-crm::activities.actions.log'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->model(LiteCrm::model(Activity::class))
            ->schema(fn (Schema $schema): Schema => ActivityResource::form($schema))
            ->visible(function (Component $livewire): bool {
                $record = static::pageRecord($livewire);

                return $record !== null
                    && LiteCrm::isModuleEnabled('activities')
                    && method_exists($record, 'activities')
                    && Gate::allows('create', LiteCrm::model(Activity::class))
                    && Gate::allows('view', $record);
            })
            ->using(function (array $data, Component $livewire): Model {
                $record = static::pageRecord($livewire);

                if ($record === null || ! method_exists($record, 'activities')) {
                    throw new LogicException('Log activity is only available on a record page.');
                }

                /** @var Model $activity */
                $activity = $record->activities()->create($data);

                return $activity;
            })
            ->successNotificationTitle(__('lite-crm::activities.notifications.logged'));
    }

    /**
     * The record of the view or edit page the action is on.
     */
    protected static function pageRecord(Component $livewire): ?Model
    {
        if (! $livewire instanceof ViewRecord && ! $livewire instanceof EditRecord) {
            return null;
        }

        return $livewire->getRecord();
    }
}
