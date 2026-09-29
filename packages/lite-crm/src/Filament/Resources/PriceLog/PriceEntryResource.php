<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\PriceLog;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Enums\PriceBasis;
use LiteCrm\Enums\PriceConfidence;
use LiteCrm\Enums\PriceSourceType;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\PriceEntry;

/**
 * The price log: observed prices by product, basis and source. Internal only.
 */
class PriceEntryResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 75;

    protected static ?string $slug = 'price-log';

    public static function getModel(): string
    {
        return LiteCrm::model(PriceEntry::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::price-log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::price-log.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            DatePicker::make('observed_on')->label(__('lite-crm::price-log.fields.observed_on'))->default(fn (): string => now()->toDateString())->required(),
            Fields::product(),
            Select::make('source_type')->label(__('lite-crm::price-log.fields.source_type'))->options(PriceSourceType::class)->required(),
            Fields::organisation('source_organisation_id', 'sourceOrganisation', __('lite-crm::price-log.fields.source_organisation')),
            Select::make('basis')->label(__('lite-crm::price-log.fields.basis'))->options(PriceBasis::class)->required(),
            TextInput::make('location')->label(__('lite-crm::price-log.fields.location'))->maxLength(255),
            TextInput::make('price')->label(__('lite-crm::price-log.fields.price'))->numeric()->minValue(0)->required(),
            Fields::currency()->required(),
            TextInput::make('unit')->label(__('lite-crm::price-log.fields.unit'))->required()->maxLength(30),
            DatePicker::make('valid_until')->label(__('lite-crm::price-log.fields.valid_until'))->afterOrEqual('observed_on'),
            TextInput::make('reference')->label(__('lite-crm::price-log.fields.reference'))->maxLength(255),
            Select::make('confidence')->label(__('lite-crm::price-log.fields.confidence'))->options(PriceConfidence::class)->default(PriceConfidence::Reported)->required(),
            Textarea::make('notes')->label(__('lite-crm::price-log.fields.notes'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('observed_on')->label(__('lite-crm::price-log.fields.observed_on'))->date()->sortable(),
                TextColumn::make('product.name')->label(__('lite-crm::products.label'))->searchable(),
                TextColumn::make('basis')->label(__('lite-crm::price-log.fields.basis'))->badge(),
                TextColumn::make('price')
                    ->label(__('lite-crm::price-log.fields.price'))
                    ->formatStateUsing(fn (PriceEntry $record): string => $record->currency.' '.rtrim(rtrim((string) $record->price, '0'), '.').' / '.$record->unit)
                    ->sortable(),
                TextColumn::make('location')->label(__('lite-crm::price-log.fields.location'))->toggleable(),
                TextColumn::make('source_type')->label(__('lite-crm::price-log.fields.source_type'))->toggleable(),
                TextColumn::make('confidence')->label(__('lite-crm::price-log.fields.confidence'))->badge(),
            ])
            ->defaultSort('observed_on', 'desc')
            ->filters([
                SelectFilter::make('product_id')->label(__('lite-crm::products.label'))->relationship('product', 'name'),
                SelectFilter::make('basis')->label(__('lite-crm::price-log.fields.basis'))->options(PriceBasis::class),
                SelectFilter::make('source_type')->label(__('lite-crm::price-log.fields.source_type'))->options(PriceSourceType::class),
                SelectFilter::make('confidence')->label(__('lite-crm::price-log.fields.confidence'))->options(PriceConfidence::class),
                Filter::make('observed_on')
                    ->schema([
                        DatePicker::make('from')->label(__('lite-crm::common.filters.from')),
                        DatePicker::make('until')->label(__('lite-crm::common.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('observed_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('observed_on', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([FormLayout::wide(EditAction::make()), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePriceEntries::route('/'),
        ];
    }
}
