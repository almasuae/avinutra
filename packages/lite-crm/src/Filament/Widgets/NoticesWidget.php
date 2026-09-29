<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Announcement;
use LiteCrm\Models\Decision;
use LiteCrm\Support\Permissions;

/**
 * Pinned announcements and the latest decisions.
 */
class NoticesWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 10;

    protected string $view = 'lite-crm::filament.widgets.notices';

    protected static function module(): string
    {
        return 'announcements';
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return (LiteCrm::isModuleEnabled('announcements') && Permissions::allows($user, 'announcements.view'))
            || (LiteCrm::isModuleEnabled('decisions') && Permissions::allows($user, 'decisions.view'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        return [
            'announcements' => LiteCrm::isModuleEnabled('announcements') && Permissions::allows($user, 'announcements.view')
                ? $this->scoped(Announcement::class, null)->where('pinned', true)->latest()->limit(3)->get()
                : collect(),
            'decisions' => LiteCrm::isModuleEnabled('decisions') && Permissions::allows($user, 'decisions.view')
                ? $this->scoped(Decision::class, null)->latest('decided_on')->limit(5)->get()
                : collect(),
        ];
    }
}
