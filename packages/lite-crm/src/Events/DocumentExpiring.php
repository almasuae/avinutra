<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Document;

/**
 * A document expires within the warning window (fired by the weekly report).
 */
class DocumentExpiring
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Document $document, public int $daysLeft) {}
}
