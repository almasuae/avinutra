<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\Models\Document;

/**
 * Documents and certificates that expire within the warning window, or have just expired.
 */
class ExpiringDocumentsWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 5;

    protected string $view = 'lite-crm::filament.widgets.expiring-documents';

    protected static function module(): string
    {
        return 'documents';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $days = (int) config('lite-crm.notifications.expiry_warning_days', 60);

        return [
            'days' => $days,
            'documents' => $this->scoped(Document::class)
                ->with('documentable')
                ->whereNotNull('expires_on')
                ->whereDate('expires_on', '>=', Date::today()->subDays(30))
                ->whereDate('expires_on', '<=', Date::today()->addDays($days))
                ->orderBy('expires_on')
                ->limit(10)
                ->get(),
        ];
    }
}
