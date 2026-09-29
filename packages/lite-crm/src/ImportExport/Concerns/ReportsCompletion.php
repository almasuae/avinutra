<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport\Concerns;

use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;

/**
 * The messages shown when an import or export finishes.
 */
trait ReportsCompletion
{
    public static function getCompletedNotificationBody(Import|Export $run): string
    {
        $body = __('lite-crm::import.completed', ['count' => number_format($run->successful_rows)]);
        $failed = $run->total_rows - $run->successful_rows;

        if ($failed > 0) {
            $body .= ' '.__('lite-crm::import.failed_rows', ['count' => number_format($failed)]);
        }

        return $body;
    }
}
