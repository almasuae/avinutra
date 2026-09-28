<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Enquiry;

/**
 * An enquiry was assigned to someone (or reassigned).
 */
class EnquiryAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Enquiry $enquiry) {}
}
