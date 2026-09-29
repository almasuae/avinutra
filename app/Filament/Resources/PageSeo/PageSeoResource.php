<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSeo;

use App\Filament\Resources\WebsiteResource;
use App\Models\PageSeo;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use LiteCrm\Filament\FormLayout;

/**
 * SEO title and description per public page. Empty fields keep the page's own text.
 */
class PageSeoResource extends WebsiteResource
{
    protected static ?string $model = PageSeo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Page SEO';

    protected static ?string $modelLabel = 'page SEO';

    protected static ?string $pluralModelLabel = 'page SEO';

    /**
     * Public GET pages, by route name.
     *
     * @return array<string, string>
     */
    public static function publicRoutes(): array
    {
        return collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => in_array('GET', $route->methods(), true)
                && $route->getName() !== null
                && ! str_starts_with((string) $route->getName(), 'filament.')
                && ! str_starts_with((string) $route->getName(), 'livewire.')
                && ! str_starts_with((string) $route->getName(), 'dev.')
                && ! str_starts_with((string) $route->getName(), 'lite-crm.')
                && ! str_starts_with($route->uri(), config('lite-crm.path', 'crm'))
                && in_array('web', $route->gatherMiddleware(), true))
            ->mapWithKeys(fn (Route $route): array => [(string) $route->getName() => '/'.ltrim($route->uri(), '/').' ('.$route->getName().')'])
            ->sort()
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('route_name')->label('Page')->options(fn (): array => self::publicRoutes())->searchable()->required()->unique(ignoreRecord: true)->columnSpanFull(),
            TextInput::make('title')->label('Title (max. 70 characters)')->maxLength(70)->columnSpanFull(),
            Textarea::make('description')->label('Description (max. 170 characters)')->maxLength(170)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('route_name')->label('Page')->searchable()->sortable(),
                TextColumn::make('title')->limit(60),
                TextColumn::make('description')->limit(80)->toggleable(),
            ])
            ->defaultSort('route_name')
            ->recordActions([FormLayout::wide(EditAction::make()), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePageSeo::route('/')];
    }
}
