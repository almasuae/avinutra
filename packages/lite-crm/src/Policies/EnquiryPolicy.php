<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class EnquiryPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'enquiries';
    }
}
