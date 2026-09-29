<?php

declare(strict_types=1);

namespace App\Filament\Resources\GlossaryTerms;

use App\Filament\Resources\WebsiteResource;
use App\Models\GlossaryTerm;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlossaryTermResource extends WebsiteResource
{
    protected static ?string $model = GlossaryTerm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Glossary';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('term')->required()->maxLength(150),
            TextInput::make('slug')->helperText('Leave empty to create it from the term.')->alphaDash()->maxLength(150)->unique(ignoreRecord: true),
            Textarea::make('definition')->required()->rows(4)->maxLength(2000)->columnSpanFull(),
            Toggle::make('is_published')->label('Published')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('term')->searchable()->sortable(),
                TextColumn::make('definition')->limit(80)->toggleable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->defaultSort('term')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageGlossaryTerms::route('/')];
    }
}
