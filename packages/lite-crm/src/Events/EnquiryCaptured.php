<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Enquiry;

/**
 * A new enquiry was stored (from a form, the API or code). Not fired for spam.
 */
class EnquiryCaptured
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Enquiry $enquiry) {}
}
