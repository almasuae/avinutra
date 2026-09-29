<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Opportunities\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Quotation;
use LiteCrm\Support\Visibility;

class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lite-crm::quotations.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return LiteCrm::isModuleEnabled('quotations') && Gate::allows('viewAny', LiteCrm::model(Quotation::class));
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return QuotationResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return QuotationResource::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => Visibility::apply($query, Filament::auth()->user()))
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $opportunity = $this->getOwnerRecord();

                    // Start from the opportunity's organisation, contact and product.
                    if ($opportunity instanceof Opportunity) {
                        $data['organisation_id'] ??= $opportunity->organisation_id;
                        $data['contact_id'] ??= $opportunity->contact_id;
                        $data['product_id'] ??= $opportunity->product_id;
                    }

                    return $data;
                }),
            ]);
    }
}
