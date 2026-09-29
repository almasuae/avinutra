<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use LiteCrm\Filament\Resources\CrmResource;

/**
 * Base for the host's Website resources (v5 §E2 "host-only admin resources"):
 * CRM › Website, for users with the "website.manage" permission. Deleting is
 * for Admins only (CrmResource).
 */
abstract class WebsiteResource extends CrmResource
{
    public const GROUP = 'Website';

    protected static ?string $viewPermission = 'website.manage';

    protected static ?string $managePermission = 'website.manage';

    public static function getNavigationGroup(): ?string
    {
        return self::GROUP;
    }
}
