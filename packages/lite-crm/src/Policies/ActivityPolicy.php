<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class ActivityPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'activities';
    }
}
