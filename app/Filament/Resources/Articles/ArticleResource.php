<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Filament\Resources\WebsiteResource;
use App\Models\Article;
use App\Models\TeamProfile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Knowledge Centre articles (v3 §7.9): draft → in review → approved → published.
 */
class ArticleResource extends WebsiteResource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 3;

    /**
     * Pages an article may link to as its related tool or page.
     *
     * @return array<string, string>
     */
    public static function relatedRoutes(): array
    {
        return [
            'tools.methionine-value' => 'Methionine Value Calculator',
            'tools.landed-cost' => 'Landed Cost Calculator',
            'tools' => 'Tools index',
            'ingredients.methionine' => 'Methionine guide',
            'ingredients' => 'Ingredients',
            'quality' => 'Quality',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $people = fn (): array => TeamProfile::query()->orderBy('name')->pluck('name', 'id')->all();

        return $schema->components([
            Section::make('Article')
                ->schema([
                    TextInput::make('title')->required()->maxLength(200)->columnSpanFull(),
                    TextInput::make('slug')->helperText('Leave empty to create it from the title.')->alphaDash()->maxLength(200)->unique(ignoreRecord: true),
                    Select::make('category')->options(ArticleCategory::class)->required(),
                    Select::make('status')->options(ArticleStatus::class)->required()->default(ArticleStatus::Draft->value)->live()
                        ->helperText('Only published articles are shown. An article written under a person\'s name stays unpublished until that person has authored or reviewed it.'),
                    DateTimePicker::make('published_at')->label('Publish date')->helperText('Empty: set when first published.'),
                    Textarea::make('summary')->rows(2)->maxLength(300)->columnSpanFull(),
                    MarkdownEditor::make('body')
                        ->columnSpanFull()
                        ->required(fn (Get $get): bool => $get('status') === ArticleStatus::Published->value || $get('status') === ArticleStatus::Published),
                    Textarea::make('outline')->rows(4)->helperText('Planning notes for drafts; never shown on the site.')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Byline and sources')
                ->schema([
                    Select::make('author_id')->label('Author')->options($people)->searchable()
                        ->helperText('Empty: "AviNutra Technical Team". A person is named only when their profile is published with consent.'),
                    Select::make('reviewer_id')->label('Reviewed by')->options($people)->searchable(),
                    DatePicker::make('last_reviewed_on')->label('Last reviewed'),
                    Select::make('related_route')->label('Related tool or page')->options(self::relatedRoutes()),
                    Repeater::make('sources')
                        ->schema([
                            TextInput::make('title')->required()->maxLength(500),
                            TextInput::make('url')->url()->maxLength(500),
                            TextInput::make('date')->maxLength(40),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')->badge()->color(fn (ArticleStatus $state): string => $state === ArticleStatus::Published ? 'success' : 'gray'),
                TextColumn::make('last_reviewed_on')->label('Last reviewed')->date()->sortable(),
                TextColumn::make('published_at')->label('Published')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ArticleStatus::class),
                SelectFilter::make('category')->options(ArticleCategory::class),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageArticles::route('/')];
    }
}
