<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\AuditLog\Pages;

use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\AuditLog\AuditLogResource;

class ListAuditLog extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;
}
