<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Samples;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Sample;

class SampleResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'samples';

    public static function getModel(): string
    {
        return LiteCrm::model(Sample::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::samples.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::samples.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Fields::organisation(),
            Fields::contact(),
            Fields::product(),
            Fields::organisation('supplier_id', 'supplier', __('lite-crm::samples.fields.supplier')),
            TextInput::make('lot_number')->label(__('lite-crm::samples.fields.lot_number'))->maxLength(255),
            TextInput::make('quantity')->label(__('lite-crm::samples.fields.quantity'))->maxLength(255),
            DatePicker::make('sent_on')->label(__('lite-crm::samples.fields.sent_on')),
            DatePicker::make('received_on')->label(__('lite-crm::samples.fields.received_on'))->afterOrEqual('sent_on'),
            TextInput::make('courier')->label(__('lite-crm::samples.fields.courier'))->maxLength(255),
            TextInput::make('tracking')->label(__('lite-crm::samples.fields.tracking'))->maxLength(255),
            Textarea::make('feedback')->label(__('lite-crm::samples.fields.feedback'))->rows(3)->columnSpanFull(),
            ...CustomFieldComponents::form('sample'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label(__('lite-crm::products.label'))->searchable(),
                TextColumn::make('organisation.name')->label(__('lite-crm::organisations.label'))->searchable(),
                TextColumn::make('stage')
                    ->label(__('lite-crm::samples.fields.stage'))
                    ->state(fn (Sample $record): string => $record->stage())
                    ->formatStateUsing(fn (string $state): string => __("lite-crm::samples.stages.{$state}"))
                    ->badge(),
                TextColumn::make('sent_on')->label(__('lite-crm::samples.fields.sent_on'))->date()->sortable(),
                TextColumn::make('received_on')->label(__('lite-crm::samples.fields.received_on'))->date()->toggleable(),
                TextColumn::make('lot_number')->label(__('lite-crm::samples.fields.lot_number'))->toggleable(isToggledHiddenByDefault: true),
                ...CustomFieldComponents::tableColumns('sample'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('in_progress')
                    ->label(__('lite-crm::samples.filters.in_progress'))
                    ->query(fn (Builder $query): Builder => $query->scopes(['inProgress'])),
                ...CustomFieldComponents::tableFilters('sample'),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSamples::route('/'),
        ];
    }
}
