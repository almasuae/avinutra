<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class ProductPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'products';
    }
}
