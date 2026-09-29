<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class PriceEntryPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'price_log';
    }
}
