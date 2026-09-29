<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Decisions;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Decision;

/**
 * The decision register. Append-only: only Admins can edit or delete entries.
 */
class DecisionResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 86;

    protected static ?string $slug = 'decisions';

    public static function getModel(): string
    {
        return LiteCrm::model(Decision::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::decisions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::decisions.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            DatePicker::make('decided_on')->label(__('lite-crm::decisions.fields.decided_on'))->default(fn (): string => now()->toDateString())->required(),
            TextInput::make('decided_by')->label(__('lite-crm::decisions.fields.decided_by'))->maxLength(255),
            TextInput::make('title')->label(__('lite-crm::decisions.fields.title'))->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('decision')->label(__('lite-crm::decisions.fields.decision'))->required()->rows(4)->columnSpanFull(),
            Textarea::make('rationale')->label(__('lite-crm::decisions.fields.rationale'))->rows(3)->columnSpanFull(),
            Fields::recordSelect('related'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('decided_on')->label(__('lite-crm::decisions.fields.decided_on'))->date(),
            TextEntry::make('decided_by')->label(__('lite-crm::decisions.fields.decided_by'))->placeholder('—'),
            TextEntry::make('decision')->label(__('lite-crm::decisions.fields.decision'))->columnSpanFull(),
            TextEntry::make('rationale')->label(__('lite-crm::decisions.fields.rationale'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('related_label')->label(__('lite-crm::common.fields.related_record'))
                ->state(fn (Decision $record): ?string => Fields::recordLabel($record->related))->placeholder('—'),
            TextEntry::make('owner.name')->label(__('lite-crm::decisions.fields.recorded_by'))->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('decided_on')->label(__('lite-crm::decisions.fields.decided_on'))->date()->sortable(),
                TextColumn::make('title')->label(__('lite-crm::decisions.fields.title'))->searchable()->wrap(),
                TextColumn::make('decided_by')->label(__('lite-crm::decisions.fields.decided_by'))->toggleable(),
                Fields::recordColumn('related'),
            ])
            ->defaultSort('decided_on', 'desc')
            ->filters([TrashedFilter::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDecisions::route('/'),
        ];
    }
}
