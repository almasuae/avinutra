<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Enquiries\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Enquiries\Actions\ConvertEnquiryAction;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\Models\Enquiry;

class ViewEnquiry extends ViewRecord
{
    protected static string $resource = EnquiryResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return $record instanceof Enquiry ? $record->displayName() : parent::getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            ConvertEnquiryAction::make(),
            Action::make('assign')
                ->label(__('lite-crm::enquiries.actions.assign'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->visible(fn (Enquiry $record): bool => $record->status->isOpen() && Gate::allows('update', $record))
                ->fillForm(fn (Enquiry $record): array => ['assignee_id' => $record->assignee_id])
                ->schema([EnquiryResource::assigneeField()])
                ->action(fn (Enquiry $record, array $data) => $record->update(['assignee_id' => $data['assignee_id']])),
            LogActivityAction::make(),
            ActionGroup::make([
                static::statusAction('start', EnquiryStatus::InProgress, Heroicon::OutlinedPlay,
                    fn (Enquiry $record): bool => in_array($record->status, [EnquiryStatus::New, EnquiryStatus::Assigned], true)),
                static::statusAction('close', EnquiryStatus::Closed, Heroicon::OutlinedCheck,
                    fn (Enquiry $record): bool => $record->status->isOpen()),
                static::statusAction('markSpam', EnquiryStatus::Spam, Heroicon::OutlinedNoSymbol,
                    fn (Enquiry $record): bool => $record->status->isOpen())->color('danger')->requiresConfirmation(),
                static::statusAction('notSpam', EnquiryStatus::New, Heroicon::OutlinedArrowUturnLeft,
                    fn (Enquiry $record): bool => $record->status === EnquiryStatus::Spam),
                static::statusAction('reopen', EnquiryStatus::InProgress, Heroicon::OutlinedArrowPath,
                    fn (Enquiry $record): bool => $record->status === EnquiryStatus::Closed),
                DeleteAction::make(),
                RestoreAction::make(),
            ]),
        ];
    }

    protected static function statusAction(string $name, EnquiryStatus $status, Heroicon $icon, \Closure $when): Action
    {
        return Action::make($name)
            ->label(__("lite-crm::enquiries.actions.{$name}"))
            ->icon($icon)
            ->visible(fn (Enquiry $record): bool => $when($record) && Gate::allows('update', $record))
            ->action(function (Enquiry $record) use ($status): void {
                $record->status = $status;

                if ($status !== EnquiryStatus::Spam) {
                    $record->spam_reason = null;
                }

                $record->save();
            });
    }
}
