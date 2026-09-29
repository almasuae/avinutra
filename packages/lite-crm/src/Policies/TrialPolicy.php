<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class TrialPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'trials';
    }
}
