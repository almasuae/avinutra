<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Tasks;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Enums\TaskPriority;
use LiteCrm\Enums\TaskStatus;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Task;

class TaskResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'tasks';

    public static function getModel(): string
    {
        return LiteCrm::model(Task::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::tasks.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::tasks.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label(__('lite-crm::tasks.fields.title'))->required()->maxLength(255)->columnSpanFull(),
            Fields::recordSelect('taskable'),
            Textarea::make('description')->label(__('lite-crm::tasks.fields.description'))->columnSpanFull(),
            Fields::assignee(),
            DateTimePicker::make('due_at')->label(__('lite-crm::tasks.fields.due_at'))->seconds(false),
            Select::make('priority')
                ->label(__('lite-crm::tasks.fields.priority'))
                ->options(TaskPriority::class)
                ->default(TaskPriority::Normal)
                ->required(),
            Select::make('status')
                ->label(__('lite-crm::tasks.fields.status'))
                ->options(TaskStatus::class)
                ->default(TaskStatus::Open)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('lite-crm::tasks.fields.title'))->searchable()->wrap(),
                Fields::recordColumn('taskable'),
                TextColumn::make('assignee.name')->label(__('lite-crm::tasks.fields.assignee')),
                TextColumn::make('due_at')
                    ->label(__('lite-crm::tasks.fields.due_at'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Task $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('priority')->label(__('lite-crm::tasks.fields.priority'))->badge()->sortable(),
                TextColumn::make('status')->label(__('lite-crm::tasks.fields.status'))->badge(),
            ])
            ->defaultSort('due_at')
            ->filters([
                SelectFilter::make('status')->label(__('lite-crm::tasks.fields.status'))->options(TaskStatus::class),
                SelectFilter::make('priority')->label(__('lite-crm::tasks.fields.priority'))->options(TaskPriority::class),
                SelectFilter::make('assignee_id')->label(__('lite-crm::tasks.fields.assignee'))->options(fn (): array => LiteCrm::userOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label(__('lite-crm::tasks.actions.complete'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Task $record): bool => ! $record->status->isClosed() && Gate::allows('update', $record))
                    ->action(fn (Task $record) => $record->markDone()),
                FormLayout::wide(EditAction::make()),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTasks::route('/'),
        ];
    }
}
