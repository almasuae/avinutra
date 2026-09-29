<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;

/**
 * Every team member's local time and city.
 */
class TeamClockWidget extends Widget
{
    protected static ?int $sort = 9;

    protected string $view = 'lite-crm::filament.widgets.team-clock';

    public static function canView(): bool
    {
        return Filament::auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $people = LiteCrm::userModel()::query()
            // Active members who have accepted their invitation (or were never invited, like the first admin).
            ->whereHas('crmProfile', fn (Builder $query) => $query
                ->where('is_active', true)
                ->where(fn (Builder $query) => $query->whereNull('invited_at')->orWhereNotNull('invitation_accepted_at')))
            ->with('crmProfile')
            ->orderBy('name')
            ->get()
            ->map(function ($user): array {
                /** @var UserProfile|null $profile */
                $profile = $user->getRelationValue('crmProfile');
                $zone = $profile->time_zone ?? 'UTC';

                return [
                    'name' => (string) $user->getAttribute('name'),
                    'city' => $profile?->city,
                    'zone' => $zone,
                    'time' => Date::now()->setTimezone($zone),
                ];
            })
            ->sortBy(fn (array $person): int => $person['time']->utcOffset());

        return ['people' => $people];
    }
}
