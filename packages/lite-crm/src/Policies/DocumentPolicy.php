<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class DocumentPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'documents';
    }
}
