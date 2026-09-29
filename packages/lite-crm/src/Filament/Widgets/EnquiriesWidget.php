<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Date;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\Models\Enquiry;

/**
 * New and unassigned enquiries, the average first-response time, and the latest five.
 */
class EnquiriesWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 2;

    protected string $view = 'lite-crm::filament.widgets.enquiries';

    protected static function module(): string
    {
        return 'enquiries';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        [$from, $until] = $this->period(Date::now()->subDays(30));

        $inbox = fn () => $this->scoped(Enquiry::class, 'assignee_id')->where('status', '!=', EnquiryStatus::Spam->value);

        // Averaged in PHP so the query stays portable (MariaDB and SQLite).
        $responses = $inbox()
            ->whereBetween('created_at', [$from, $until])
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at'])
            ->map(fn (Enquiry $enquiry): float => $enquiry->created_at->diffInMinutes($enquiry->first_response_at, true));

        return [
            'new' => $inbox()->where('status', EnquiryStatus::New->value)->count(),
            'unassigned' => $inbox()->whereNull('assignee_id')->whereIn('status', [EnquiryStatus::New->value])->count(),
            'averageHours' => $responses->isEmpty() ? null : round($responses->avg() / 60, 1),
            'latest' => $inbox()->with('type')->latest()->limit(5)->get(),
            'from' => $from,
        ];
    }
}
