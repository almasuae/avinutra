<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class QuotationPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'quotations';
    }
}
