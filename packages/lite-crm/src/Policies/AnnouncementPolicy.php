<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

class AnnouncementPolicy extends RecordPolicy
{
    protected function module(): string
    {
        return 'announcements';
    }
}
