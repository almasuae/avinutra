<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\FormLayout;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Organisation;
use LiteCrm\Support\Visibility;

/**
 * Organisations that supply the product, with notes.
 */
class SuppliersRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lite-crm::products.fields.suppliers');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('viewAny', LiteCrm::model(Organisation::class));
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('notes')->label(__('lite-crm::products.fields.supplier_notes')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => Visibility::apply($query, Filament::auth()->user()))
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::organisations.fields.name'))->searchable(),
                TextColumn::make('country')->label(__('lite-crm::organisations.fields.country')),
                TextColumn::make('notes')->label(__('lite-crm::products.fields.supplier_notes'))->limit(60)->wrap(),
            ])
            ->headerActions([
                FormLayout::wide(AttachAction::make())
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => Visibility::apply($query, Filament::auth()->user()))
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Textarea::make('notes')->label(__('lite-crm::products.fields.supplier_notes')),
                    ]),
            ])
            ->recordActions([FormLayout::wide(EditAction::make()), DetachAction::make()]);
    }
}
