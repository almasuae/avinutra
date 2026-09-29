<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Decisions are append-only: anyone allowed may record one, but only Admins
 * (who pass every check via Gate::before) may change or delete it.
 */
class DecisionPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'decisions';
    }

    public function update(Model $user, Model $record): bool
    {
        return false;
    }
}
