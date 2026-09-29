<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Shared;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Visibility;

/**
 * Activities, tasks or documents shown on an organisation or contact page,
 * using the child resource's form and table, limited to visible records.
 */
abstract class ChildRecordsRelationManager extends RelationManager
{
    /**
     * @return class-string<RecordResource>
     */
    abstract protected static function childResource(): string;

    protected function createAction(): CreateAction
    {
        return FormLayout::wide(CreateAction::make());
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::childResource()::getPluralModelLabel();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $resource = static::childResource();

        return LiteCrm::isModuleEnabled($resource::crmModule()) && Gate::allows('viewAny', $resource::getModel());
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return static::childResource()::form($schema);
    }

    public function table(Table $table): Table
    {
        return static::childResource()::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => Visibility::apply($query, Filament::auth()->user()))
            ->headerActions([$this->createAction()]);
    }
}
