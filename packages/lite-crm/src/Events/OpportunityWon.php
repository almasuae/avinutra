<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Opportunity;

class OpportunityWon
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Opportunity $opportunity) {}
}
