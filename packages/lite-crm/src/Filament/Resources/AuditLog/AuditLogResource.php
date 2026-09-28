<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\AuditLog;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Filament\Resources\CrmResource;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * Read-only audit trail of changes, logins, invitations and role changes.
 */
class AuditLogResource extends CrmResource
{
    protected static ?string $viewPermission = 'audit_log.view';

    protected static ?string $managePermission = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'crm-audit-log';

    public static function getModel(): string
    {
        return ActivitylogServiceProvider::determineActivityModel();
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::audit-log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::audit-log.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('lite-crm::audit-log.navigation');
    }

    public static function subjectLabel(Model $record): string
    {
        $type = (string) $record->getAttribute('subject_type');

        return $type === '' ? '—' : class_basename($type).' #'.$record->getAttribute('subject_id');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->label(__('lite-crm::audit-log.fields.created_at'))->dateTime(),
            TextEntry::make('causer.name')->label(__('lite-crm::audit-log.fields.causer'))->placeholder(__('lite-crm::audit-log.system')),
            TextEntry::make('event')->label(__('lite-crm::audit-log.fields.event'))->badge(),
            TextEntry::make('subject')->label(__('lite-crm::audit-log.fields.subject'))->state(fn (Model $record): string => static::subjectLabel($record)),
            KeyValueEntry::make('properties.old')->label(__('lite-crm::audit-log.fields.old'))->columnSpanFull(),
            KeyValueEntry::make('properties.attributes')->label(__('lite-crm::audit-log.fields.new'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('lite-crm::audit-log.fields.created_at'))->dateTime()->sortable(),
                TextColumn::make('causer.name')->label(__('lite-crm::audit-log.fields.causer'))->placeholder(__('lite-crm::audit-log.system')),
                TextColumn::make('event')->label(__('lite-crm::audit-log.fields.event'))->badge(),
                TextColumn::make('subject_type')
                    ->label(__('lite-crm::audit-log.fields.subject'))
                    ->state(fn (Model $record): string => static::subjectLabel($record)),
                TextColumn::make('log_name')->label(__('lite-crm::audit-log.fields.log'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label(__('lite-crm::audit-log.fields.event'))
                    ->options(fn (): array => static::getModel()::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('subject_type')
                    ->label(__('lite-crm::audit-log.fields.subject_type'))
                    ->options(fn (): array => static::getModel()::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('lite-crm::audit-log.fields.from')),
                        DatePicker::make('until')->label(__('lite-crm::audit-log.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLog::route('/'),
        ];
    }
}
