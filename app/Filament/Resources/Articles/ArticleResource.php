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
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use LiteCrm\Filament\FormLayout;

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
        // Only public profiles (published, with consent on file) can be named in a byline.
        $people = fn (): array => TeamProfile::query()->public()->orderBy('name')->pluck('name', 'id')->all();

        // Full page (CRM form layout): the text takes two thirds, the details one third;
        // one column below 1024 px.
        return $schema->columns(['default' => 1, 'lg' => 3])->components([
            Group::make([
                Section::make('Article')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(200),
                        TextInput::make('slug')->helperText('Leave empty to create it from the title.')->alphaDash()->maxLength(200)->unique(ignoreRecord: true),
                        Textarea::make('summary')->maxLength(300),
                        MarkdownEditor::make('body')
                            ->minHeight(FormLayout::TALL_EDITOR_MIN_HEIGHT)
                            ->required(fn (Get $get): bool => $get('status') === ArticleStatus::Published->value || $get('status') === ArticleStatus::Published),
                        Textarea::make('outline')->rows(8)->helperText('Planning notes for drafts; never shown on the site.'),
                    ]),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('Publishing')
                    ->schema([
                        Select::make('status')->options(ArticleStatus::class)->required()->default(ArticleStatus::Draft->value)->live()
                            ->helperText('Only published articles are shown. An article written under a person\'s name stays unpublished until that person has authored or reviewed it.'),
                        DateTimePicker::make('published_at')->label('Publish date')->helperText('Empty: set when first published.'),
                        Select::make('category')->options(ArticleCategory::class)->required(),
                    ]),
                Section::make('Byline')
                    ->schema([
                        Select::make('author_id')->label('Author')->options($people)->searchable()
                            ->in(fn (): array => array_keys($people()))
                            ->placeholder(Article::COMPANY_AUTHOR)
                            ->helperText('Only published team profiles with consent on file can be chosen. Empty: "'.Article::COMPANY_AUTHOR.'".'),
                        Select::make('reviewer_id')->label('Reviewed by')->options($people)->searchable()
                            ->in(fn (): array => array_keys($people()))
                            ->placeholder('No named reviewer')
                            ->helperText('Only published team profiles with consent on file can be chosen.'),
                        DatePicker::make('last_reviewed_on')->label('Last reviewed'),
                        Select::make('related_route')->label('Related tool or page')->options(self::relatedRoutes()),
                    ]),
                Section::make('Sources')
                    ->schema([
                        // One full-width row per source, so long titles and URLs stay readable.
                        Repeater::make('sources')->hiddenLabel()
                            ->schema([
                                Textarea::make('title')->required()->maxLength(500),
                                TextInput::make('url')->label('URL')->url()->maxLength(500),
                                TextInput::make('date')->maxLength(40),
                                Textarea::make('note')->label('Internal note')->maxLength(500)
                                    ->helperText('For the team only (e.g. why a title is shortened); never shown on the site.'),
                            ])
                            ->itemLabel(fn (array $state): string => Str::limit((string) ($state['title'] ?? ''), 60))
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
            ])->columnSpan(['lg' => 1]),
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
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
