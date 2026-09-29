<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalculatorDefaults;

use App\Filament\Resources\WebsiteResource;
use App\Models\CalculatorDefault;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Calculator defaults and Pakistan tax rates (v5 §E3), each with source, date
 * and approval. Unapproved values are labelled "Indicative default" on the site.
 */
class CalculatorDefaultResource extends WebsiteResource
{
    protected static ?string $model = CalculatorDefault::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Calculator defaults';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tool')->required()->maxLength(60)->helperText('e.g. methionine-value, landed-cost-pakistan'),
            TextInput::make('key')->required()->alphaDash()->maxLength(80),
            TextInput::make('label')->required()->maxLength(200)->columnSpanFull(),
            TextInput::make('value')->numeric(),
            TextInput::make('unit')->maxLength(40),
            TextInput::make('source')->maxLength(255)->columnSpanFull(),
            DatePicker::make('source_date')->label('Source date'),
            TextInput::make('approved_by')->label('Approved by')->maxLength(150),
            DatePicker::make('approved_on')->label('Approved on')->requiredWith('approved_by'),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tool')->sortable()->badge(),
                TextColumn::make('label')->searchable()->wrap(),
                TextColumn::make('value')->numeric(decimalPlaces: 4),
                TextColumn::make('unit'),
                TextColumn::make('source_date')->label('Source date')->date(),
                IconColumn::make('approved_on')->label('Approved')->boolean(fn (CalculatorDefault $record): bool => $record->isApproved()),
            ])
            ->filters([SelectFilter::make('tool')->options(fn (): array => CalculatorDefault::query()->distinct()->pluck('tool', 'tool')->all())])
            ->defaultSort('tool')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCalculatorDefaults::route('/')];
    }
}
