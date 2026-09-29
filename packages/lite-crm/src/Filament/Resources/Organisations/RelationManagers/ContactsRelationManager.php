<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\Contacts\ContactResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Support\Visibility;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lite-crm::contacts.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return LiteCrm::isModuleEnabled('contacts') && Gate::allows('viewAny', LiteCrm::model(Contact::class));
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return ContactResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ContactResource::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => Visibility::apply($query, Filament::auth()->user()))
            ->headerActions([FormLayout::wide(CreateAction::make())]);
    }
}
