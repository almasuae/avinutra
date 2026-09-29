<?php

declare(strict_types=1);

namespace LiteCrm\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatars drawn locally as an inline SVG. Filament's default provider
 * loads images from ui-avatars.com, which would send user names to a third party
 * and break "no external requests" (and a strict Content-Security-Policy).
 */
class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment), 0, 1)))
            ->filter()
            ->take(2)
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#111827"/>'
            .'<text x="50%" y="50%" dy=".35em" fill="#ffffff" font-family="system-ui,-apple-system,Segoe UI,sans-serif" font-size="26" font-weight="600" text-anchor="middle">'
            .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
