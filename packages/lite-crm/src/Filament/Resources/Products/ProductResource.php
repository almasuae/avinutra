<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Products;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Enums\ProductAvailability;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Product;
use LiteCrm\Support\Permissions;

class ProductResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return LiteCrm::model(Product::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::products.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::products.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $user = fn (): mixed => Filament::auth()->user();

        return $schema->columns(1)->components([
            Section::make(__('lite-crm::products.sections.details'))->columns(2)->schema([
                TextInput::make('name')->label(__('lite-crm::products.fields.name'))->required()->maxLength(255),
                TextInput::make('slug')
                    ->label(__('lite-crm::products.fields.slug'))
                    ->helperText(__('lite-crm::products.fields.slug_help'))
                    ->alphaDash()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Fields::lookup('category_id', 'product_category', __('lite-crm::products.fields.category'))->live(),
                Select::make('availability')
                    ->label(__('lite-crm::products.fields.availability'))
                    ->helperText(__('lite-crm::products.fields.availability_help'))
                    ->options(ProductAvailability::class)
                    ->default(ProductAvailability::Information)
                    ->disableOptionWhen(fn (string $value): bool => $value === ProductAvailability::Available->value
                        && ! Permissions::allows($user(), 'products.mark_available'))
                    ->required(),
                Toggle::make('publish_on_website')
                    ->label(__('lite-crm::products.fields.publish_on_website'))
                    ->disabled(fn (): bool => ! Permissions::allows($user(), 'website.manage')),
                Fields::tags(),
                Textarea::make('description')->label(__('lite-crm::products.fields.description'))->columnSpanFull(),
            ]),
            Section::make(__('lite-crm::products.fields.specification'))->schema([
                Repeater::make('specification')
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('parameter')->label(__('lite-crm::products.fields.parameter'))->required()->maxLength(255),
                        TextInput::make('value')->label(__('lite-crm::products.fields.value'))->required()->maxLength(255),
                        TextInput::make('unit')->label(__('lite-crm::products.fields.unit'))->maxLength(50),
                    ])
                    ->columns(3)
                    ->reorderable()
                    ->defaultItems(0),
            ]),
            Section::make(__('lite-crm::products.sections.handling'))->columns(3)->schema([
                TextInput::make('packaging')->label(__('lite-crm::products.fields.packaging'))->maxLength(255),
                TextInput::make('storage')->label(__('lite-crm::products.fields.storage'))->maxLength(255),
                TextInput::make('shelf_life')->label(__('lite-crm::products.fields.shelf_life'))->maxLength(255),
            ]),
            ...CustomFieldComponents::form('product', fn (Get $get): ?string => Lookup::keyFor($get('category_id'))),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::products.sections.details'))->columns(3)->schema([
                TextEntry::make('category.label')->label(__('lite-crm::products.fields.category'))->badge()->placeholder('—'),
                TextEntry::make('availability')->label(__('lite-crm::products.fields.availability'))->badge(),
                IconEntry::make('publish_on_website')->label(__('lite-crm::products.fields.publish_on_website'))->boolean(),
                TextEntry::make('description')->label(__('lite-crm::products.fields.description'))->placeholder('—')->columnSpanFull(),
            ]),
            Section::make(__('lite-crm::products.fields.specification'))->schema([
                RepeatableEntry::make('specification')->hiddenLabel()->schema([
                    TextEntry::make('parameter')->label(__('lite-crm::products.fields.parameter')),
                    TextEntry::make('value')->label(__('lite-crm::products.fields.value')),
                    TextEntry::make('unit')->label(__('lite-crm::products.fields.unit'))->placeholder('—'),
                ])->columns(3)->placeholder(__('lite-crm::products.no_specification')),
            ]),
            Section::make(__('lite-crm::products.sections.handling'))->columns(3)->schema([
                TextEntry::make('packaging')->label(__('lite-crm::products.fields.packaging'))->placeholder('—'),
                TextEntry::make('storage')->label(__('lite-crm::products.fields.storage'))->placeholder('—'),
                TextEntry::make('shelf_life')->label(__('lite-crm::products.fields.shelf_life'))->placeholder('—'),
            ]),
            ...CustomFieldComponents::infolist('product'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::products.fields.name'))->searchable()->sortable(),
                TextColumn::make('category.label')->label(__('lite-crm::products.fields.category'))->badge(),
                TextColumn::make('availability')->label(__('lite-crm::products.fields.availability'))->badge(),
                IconColumn::make('publish_on_website')->label(__('lite-crm::products.fields.published'))->boolean(),
                TextColumn::make('suppliers_count')->label(__('lite-crm::products.fields.suppliers'))->counts('suppliers')->toggleable(),
                ...CustomFieldComponents::tableColumns('product'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category_id')->label(__('lite-crm::products.fields.category'))->options(fn (): array => Lookup::options('product_category')),
                SelectFilter::make('availability')->label(__('lite-crm::products.fields.availability'))->options(ProductAvailability::class),
                TernaryFilter::make('publish_on_website')->label(__('lite-crm::products.fields.published')),
                ...CustomFieldComponents::tableFilters('product'),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('organisations') ? RelationManagers\SuppliersRelationManager::class : null,
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
            LiteCrm::isModuleEnabled('tasks') ? TasksRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
