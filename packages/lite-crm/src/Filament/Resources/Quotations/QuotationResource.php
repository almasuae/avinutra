<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Quotations;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Enums\Incoterm;
use LiteCrm\Enums\QuotationStatus;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Quotation;
use LiteCrm\Support\Visibility;
use Livewire\Component;

class QuotationResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'quotations';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModel(): string
    {
        return LiteCrm::model(Quotation::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::quotations.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::quotations.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::quotations.sections.customer'))->columns(2)->schema([
                TextInput::make('number')
                    ->label(__('lite-crm::quotations.fields.number'))
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder(__('lite-crm::quotations.fields.number_auto')),
                Select::make('status')->label(__('lite-crm::quotations.fields.status'))->options(QuotationStatus::class)->default(QuotationStatus::Draft)->required(),
                Fields::organisation(),
                Fields::contact(),
                Select::make('opportunity_id')
                    ->label(__('lite-crm::opportunities.label'))
                    ->relationship('opportunity', 'name', fn (Builder $query) => Visibility::apply($query, Filament::auth()->user()))
                    ->searchable()
                    ->visible(fn (Component $livewire): bool => LiteCrm::isModuleEnabled('opportunities') && ! $livewire instanceof RelationManager),
                TextInput::make('contracting_entity')
                    ->label(__('lite-crm::quotations.fields.contracting_entity'))
                    ->helperText(__('lite-crm::quotations.fields.contracting_entity_help'))
                    ->default(fn (): ?string => LiteCrm::contractingEntity())
                    ->maxLength(255),
            ]),
            Section::make(__('lite-crm::quotations.sections.offer'))->columns(3)->schema([
                Fields::product(),
                TextInput::make('quantity')->label(__('lite-crm::quotations.fields.quantity'))->numeric()->minValue(0),
                TextInput::make('unit')->label(__('lite-crm::quotations.fields.unit'))->maxLength(30),
                TextInput::make('price')->label(__('lite-crm::quotations.fields.price'))->numeric()->minValue(0),
                Fields::currency(),
                Select::make('incoterm')->label(__('lite-crm::quotations.fields.incoterm'))->options(Incoterm::class),
                TextInput::make('port')->label(__('lite-crm::quotations.fields.port'))->maxLength(255),
                TextInput::make('payment_terms')->label(__('lite-crm::quotations.fields.payment_terms'))->maxLength(255),
                DatePicker::make('valid_until')
                    ->label(__('lite-crm::quotations.fields.valid_until'))
                    ->default(fn (): string => now()->addDays((int) config('lite-crm.quotations.default_validity_days', 30))->toDateString()),
                Textarea::make('notes')->label(__('lite-crm::quotations.fields.notes'))->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::quotations.sections.customer'))->columns(3)->schema([
                TextEntry::make('number')->label(__('lite-crm::quotations.fields.number')),
                TextEntry::make('status')->label(__('lite-crm::quotations.fields.status'))->badge(),
                TextEntry::make('contracting_entity')->label(__('lite-crm::quotations.fields.contracting_entity'))->placeholder('—'),
                TextEntry::make('organisation.name')->label(__('lite-crm::organisations.label'))->placeholder('—'),
                TextEntry::make('contact.name')->label(__('lite-crm::contacts.label'))->placeholder('—'),
                TextEntry::make('opportunity.name')->label(__('lite-crm::opportunities.label'))->placeholder('—'),
            ]),
            Section::make(__('lite-crm::quotations.sections.offer'))->columns(3)->schema([
                TextEntry::make('product.name')->label(__('lite-crm::products.label'))->placeholder('—'),
                TextEntry::make('quantity')->label(__('lite-crm::quotations.fields.quantity'))
                    ->formatStateUsing(fn (Quotation $record): string => trim($record->quantity.' '.$record->unit))->placeholder('—'),
                TextEntry::make('price')->label(__('lite-crm::quotations.fields.price'))
                    ->formatStateUsing(fn (Quotation $record): string => trim(($record->currency ?? '').' '.$record->price))->placeholder('—'),
                TextEntry::make('total')->label(__('lite-crm::quotations.fields.total'))
                    ->state(fn (Quotation $record): ?string => $record->total() === null ? null : trim(($record->currency ?? '').' '.number_format((float) $record->total(), 2)))
                    ->placeholder('—'),
                TextEntry::make('incoterm')->label(__('lite-crm::quotations.fields.incoterm'))
                    ->formatStateUsing(fn (Quotation $record): string => trim(($record->incoterm->value ?? '').' '.($record->port ?? '')))->placeholder('—'),
                TextEntry::make('payment_terms')->label(__('lite-crm::quotations.fields.payment_terms'))->placeholder('—'),
                TextEntry::make('valid_until')->label(__('lite-crm::quotations.fields.valid_until'))->date()->placeholder('—')
                    ->color(fn (Quotation $record): ?string => $record->isExpired() ? 'danger' : null),
                TextEntry::make('notes')->label(__('lite-crm::quotations.fields.notes'))->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label(__('lite-crm::quotations.fields.number'))->searchable()->sortable(),
                TextColumn::make('organisation.name')->label(__('lite-crm::organisations.label'))->searchable(),
                TextColumn::make('product.name')->label(__('lite-crm::products.label'))->toggleable(),
                TextColumn::make('total')
                    ->label(__('lite-crm::quotations.fields.total'))
                    ->state(fn (Quotation $record): ?string => $record->total() === null ? null : trim(($record->currency ?? '').' '.number_format((float) $record->total(), 2)))
                    ->placeholder('—'),
                TextColumn::make('status')->label(__('lite-crm::quotations.fields.status'))->badge(),
                TextColumn::make('valid_until')->label(__('lite-crm::quotations.fields.valid_until'))->date()->sortable()
                    ->color(fn (Quotation $record): ?string => $record->isExpired() ? 'danger' : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('lite-crm::quotations.fields.status'))->options(QuotationStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
