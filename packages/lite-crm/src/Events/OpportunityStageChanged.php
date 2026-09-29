<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\PipelineStage;

/**
 * An opportunity moved from one stage to another (board drag, edit or code).
 */
class OpportunityStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Opportunity $opportunity,
        public ?PipelineStage $from,
        public PipelineStage $to,
    ) {}
}
