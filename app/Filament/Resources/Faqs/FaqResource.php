<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\WebsiteResource;
use App\Models\Faq;
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
use LiteCrm\Filament\FormLayout;

/**
 * FAQs (v3 §7.9; published on the site from Phase 1, with FAQ schema).
 */
class FaqResource extends WebsiteResource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('answer')->required()->rows(5)->maxLength(3000)->columnSpanFull(),
            TextInput::make('category')->maxLength(60),
            TextInput::make('sort')->numeric()->default(0),
            Toggle::make('is_published')->label('Published'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')->searchable()->wrap(),
                TextColumn::make('category')->sortable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort')
            ->recordActions([FormLayout::wide(EditAction::make()), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFaqs::route('/')];
    }
}
