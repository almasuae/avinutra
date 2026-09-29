<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Announcements;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Users\UserResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Announcement;
use LiteCrm\Support\CrmSettings;

class AnnouncementResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 85;

    protected static ?string $slug = 'announcements';

    public static function getModel(): string
    {
        return LiteCrm::model(Announcement::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::announcements.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::announcements.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('lite-crm::announcements.fields.title'))->required()->maxLength(255),
            MarkdownEditor::make('body')
                ->label(__('lite-crm::announcements.fields.body'))
                ->required()
                ->disableToolbarButtons(['attachFiles']),
            Toggle::make('pinned')->label(__('lite-crm::announcements.fields.pinned')),
            CheckboxList::make('audience_roles')
                ->label(__('lite-crm::announcements.fields.audience'))
                ->helperText(__('lite-crm::announcements.fields.audience_help'))
                ->options(fn (): array => UserResource::roleOptions())
                ->columns(3),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('body')
                ->hiddenLabel()
                ->state(fn (Announcement $record) => $record->bodyHtml())
                ->html(),
            TextEntry::make('owner.name')->label(__('lite-crm::announcements.fields.author'))->placeholder('—'),
            TextEntry::make('created_at')->label(__('lite-crm::announcements.fields.published'))->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $settings = app(CrmSettings::class);

        return $table
            ->columns([
                IconColumn::make('pinned')->label(__('lite-crm::announcements.fields.pinned'))->boolean()->trueIcon(Heroicon::Star)->falseIcon(null),
                TextColumn::make('title')->label(__('lite-crm::announcements.fields.title'))->searchable()->wrap(),
                TextColumn::make('audience_roles')
                    ->label(__('lite-crm::announcements.fields.audience'))
                    ->formatStateUsing(fn (string $state): string => $settings->roleLabel($state))
                    ->badge()
                    ->placeholder(__('lite-crm::announcements.everyone')),
                TextColumn::make('owner.name')->label(__('lite-crm::announcements.fields.author'))->toggleable(),
                TextColumn::make('created_at')->label(__('lite-crm::announcements.fields.published'))->dateTime()->sortable(),
            ])
            ->defaultSort(fn ($query) => $query->orderByDesc('pinned')->orderByDesc('created_at'))
            ->filters([TrashedFilter::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAnnouncements::route('/'),
        ];
    }
}
