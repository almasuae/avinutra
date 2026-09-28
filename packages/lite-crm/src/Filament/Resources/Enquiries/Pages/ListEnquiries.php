<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Enquiries\Pages;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;

/**
 * Inbox tabs: New · Mine · Open · All (not spam) · Spam.
 */
class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // An enquiry taken by phone or in person.
            CreateAction::make()
                ->label(__('lite-crm::enquiries.actions.log_manual'))
                ->model(LiteCrm::model(Enquiry::class))
                ->using(function (array $data): Enquiry {
                    /** @var Enquiry $enquiry */
                    $enquiry = LiteCrm::model(Enquiry::class)::query()->make($data);
                    $enquiry->forceFill(['channel' => 'manual'])->save();

                    return $enquiry;
                }),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'new' => Tab::make(__('lite-crm::enquiries.tabs.new'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', EnquiryStatus::New->value)),
            'mine' => Tab::make(__('lite-crm::enquiries.tabs.mine'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('assignee_id', Filament::auth()->id())
                    ->whereIn('status', [EnquiryStatus::Assigned->value, EnquiryStatus::InProgress->value])),
            'open' => Tab::make(__('lite-crm::enquiries.tabs.open'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [
                    EnquiryStatus::New->value, EnquiryStatus::Assigned->value, EnquiryStatus::InProgress->value,
                ])),
            'all' => Tab::make(__('lite-crm::enquiries.tabs.all'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', '!=', EnquiryStatus::Spam->value)),
            'spam' => Tab::make(__('lite-crm::enquiries.tabs.spam'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', EnquiryStatus::Spam->value)),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'new';
    }
}
