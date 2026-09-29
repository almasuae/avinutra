<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class SamplePolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'samples';
    }
}
