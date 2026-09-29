<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class OpportunityPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'opportunities';
    }
}
