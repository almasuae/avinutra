<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Support;

use Filament\Facades\Filament;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Support\Permissions;
use LiteCrm\Support\Visibility;
use Livewire\Component;

/**
 * Form fields and columns shared by the record resources.
 */
class Fields
{
    public static function lookup(string $name, string $type, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => Lookup::options($type))
            ->searchable()
            ->preload();
    }

    public static function owner(): Select
    {
        return Select::make('owner_id')
            ->label(__('lite-crm::common.fields.owner'))
            ->options(fn (): array => LiteCrm::userOptions())
            ->default(fn (): mixed => Filament::auth()->id())
            ->searchable();
    }

    public static function assignee(string $name = 'assignee_id'): Select
    {
        return Select::make($name)
            ->label(__('lite-crm::tasks.fields.assignee'))
            ->options(fn (): array => LiteCrm::userOptions())
            ->default(fn (): mixed => Filament::auth()->id())
            ->searchable();
    }

    public static function tags(): Select
    {
        return Select::make('tags')
            ->label(__('lite-crm::common.fields.tags'))
            ->relationship('tags', 'name')
            ->multiple()
            ->preload()
            ->createOptionForm(fn (): ?array => Permissions::allows(Filament::auth()->user(), 'lookups.manage')
                ? [TextInput::make('name')->label(__('lite-crm::tags.fields.name'))->required()->maxLength(255)]
                : null);
    }

    /**
     * Picks the organisation or contact a child record belongs to. Hidden inside
     * a relation manager, where the parent is known.
     */
    public static function recordSelect(string $relationship, bool $required = false): MorphToSelect
    {
        $user = fn (): mixed => Filament::auth()->user();

        return MorphToSelect::make($relationship)
            ->label(__('lite-crm::common.fields.related_record'))
            ->types([
                Type::make(LiteCrm::model(Organisation::class))
                    ->label(__('lite-crm::organisations.label'))
                    ->titleAttribute('name')
                    ->modifyOptionsQueryUsing(fn (Builder $query) => Visibility::apply($query, $user())),
                Type::make(LiteCrm::model(Contact::class))
                    ->label(__('lite-crm::contacts.label'))
                    ->titleAttribute('last_name')
                    ->getOptionLabelFromRecordUsing(fn (Contact $record): string => $record->name)
                    ->modifyOptionsQueryUsing(fn (Builder $query) => Visibility::apply($query, $user())),
            ])
            ->searchable()
            ->required($required)
            ->columnSpanFull()
            ->hidden(fn (Component $livewire): bool => static::hasParentRecord($livewire));
    }

    /**
     * Whether the form or table is shown under a known parent record (a relation
     * manager, or an action on a record page).
     */
    public static function hasParentRecord(Component $livewire): bool
    {
        return $livewire instanceof RelationManager
            || $livewire instanceof EditRecord
            || $livewire instanceof ViewRecord;
    }

    /**
     * The record a child belongs to, as "Organisation: Name".
     */
    public static function recordColumn(string $relationship): TextColumn
    {
        return TextColumn::make($relationship.'_label')
            ->label(__('lite-crm::common.fields.related_record'))
            ->state(fn (Model $record): ?string => static::recordLabel($record->getRelationValue($relationship)))
            ->placeholder('—')
            ->hidden(fn (Component $livewire): bool => static::hasParentRecord($livewire));
    }

    public static function recordLabel(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        $alias = array_search($record::class, Relation::morphMap(), true);
        $type = match ($alias) {
            'crm_organisation' => __('lite-crm::organisations.label'),
            'crm_contact' => __('lite-crm::contacts.label'),
            default => class_basename($record),
        };

        return str($type)->ucfirst().': '.($record->getAttribute('name') ?? '#'.$record->getKey());
    }
}
