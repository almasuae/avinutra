<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\ExchangeRates;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\ExchangeRate;

/**
 * Exchange rates to the base currency, for pipeline and dashboard totals.
 */
class ExchangeRateResource extends CrmResource
{
    protected static ?string $viewPermission = 'settings.manage';

    protected static ?string $managePermission = 'settings.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'exchange-rates';

    public static function getModel(): string
    {
        return LiteCrm::model(ExchangeRate::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::exchange-rates.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::exchange-rates.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $currencies = array_values(array_diff(LiteCrm::currencies(), [LiteCrm::baseCurrency()]));

        return $schema->columns(2)->components([
            Select::make('currency')
                ->label(__('lite-crm::exchange-rates.fields.currency'))
                ->options(array_combine($currencies, $currencies) ?: [])
                ->required(),
            DatePicker::make('valid_from')
                ->label(__('lite-crm::exchange-rates.fields.valid_from'))
                ->default(fn (): string => now()->toDateString())
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('currency', $get('currency'))),
            TextInput::make('rate')
                ->label(fn (Get $get): string => __('lite-crm::exchange-rates.fields.rate', ['currency' => $get('currency') ?: '…', 'base' => LiteCrm::baseCurrency()]))
                ->numeric()
                ->minValue(0.00000001)
                ->required(),
            TextInput::make('source')
                ->label(__('lite-crm::exchange-rates.fields.source'))
                ->helperText(__('lite-crm::exchange-rates.fields.source_help'))
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency')->label(__('lite-crm::exchange-rates.fields.currency'))->badge()->sortable(),
                TextColumn::make('rate')
                    ->label(fn (): string => __('lite-crm::exchange-rates.fields.rate_column', ['base' => LiteCrm::baseCurrency()]))
                    ->formatStateUsing(fn (ExchangeRate $record): string => rtrim(rtrim((string) $record->rate, '0'), '.')),
                TextColumn::make('valid_from')->label(__('lite-crm::exchange-rates.fields.valid_from'))->date()->sortable(),
                TextColumn::make('source')->label(__('lite-crm::exchange-rates.fields.source'))->toggleable(),
            ])
            ->defaultSort('valid_from', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExchangeRates::route('/'),
        ];
    }
}
