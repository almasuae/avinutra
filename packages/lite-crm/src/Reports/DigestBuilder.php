<?php

declare(strict_types=1);

namespace LiteCrm\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Document;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Task;
use LiteCrm\Support\Money;
use LiteCrm\Support\Permissions;
use LiteCrm\Support\Visibility;

/**
 * Gathers what a user should see in the daily digest and the weekly report.
 * Everything respects the user's visibility and the enabled modules.
 */
class DigestBuilder
{
    /**
     * @return array{tasks: list<string>, enquiries: list<string>, next_steps: list<string>, new_enquiries: int}
     */
    public function daily(Model&CrmUser $user): array
    {
        $tasks = [];
        $enquiries = [];
        $nextSteps = [];
        $newEnquiries = 0;

        if ($this->uses('tasks', $user)) {
            $tasks = Visibility::apply(LiteCrm::model(Task::class)::query(), $user)
                ->scopes(['open'])
                ->where('assignee_id', $user->getKey())
                ->whereNotNull('due_at')
                ->where('due_at', '<=', Date::now()->endOfDay())
                ->orderBy('due_at')
                ->limit(20)
                ->get()
                ->map(fn (Task $task): string => $task->title.' — '.$task->due_at?->setTimezone($user->crmTimezone())->format('j M'))
                ->all();
        }

        if ($this->uses('enquiries', $user)) {
            $enquiries = LiteCrm::model(Enquiry::class)::query()
                ->where('assignee_id', $user->getKey())
                ->whereIn('status', [EnquiryStatus::Assigned->value, EnquiryStatus::InProgress->value])
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (Enquiry $enquiry): string => $enquiry->displayName())
                ->all();

            /** @var list<string> $roles */
            $roles = config('lite-crm.enquiries.notify_roles', []);

            if ($roles !== [] && $user->hasAnyRole($roles)) {
                $newEnquiries = LiteCrm::model(Enquiry::class)::query()->where('status', EnquiryStatus::New->value)->count();
            }
        }

        if ($this->uses('opportunities', $user)) {
            $nextSteps = Visibility::apply(LiteCrm::model(Opportunity::class)::query(), $user)
                ->where('owner_id', $user->getKey())
                ->whereNull('closed_at')
                ->whereNotNull('next_step_date')
                ->whereDate('next_step_date', '<=', Date::today()->addDays(7))
                ->orderBy('next_step_date')
                ->limit(20)
                ->get()
                ->map(fn (Opportunity $opportunity): string => $opportunity->name.': '.($opportunity->next_step ?? '—').' — '.$opportunity->next_step_date?->format('j M'))
                ->all();
        }

        return ['tasks' => $tasks, 'enquiries' => $enquiries, 'next_steps' => $nextSteps, 'new_enquiries' => $newEnquiries];
    }

    /**
     * Documents the user owns that expire within the warning window, and the
     * user's open opportunities with no change or activity for N days.
     *
     * Also lists exchange rates older than the stale-rate limit that convert the
     * user's visible open opportunities.
     *
     * @return array{documents: list<Document>, stale: list<Opportunity>, stale_rates: array<string, string>}
     */
    public function weekly(Model&CrmUser $user): array
    {
        $documents = [];
        $stale = [];
        $staleRates = [];

        if ($this->uses('documents', $user)) {
            /** @var list<Document> $documents */
            $documents = LiteCrm::model(Document::class)::query()
                ->where('owner_id', $user->getKey())
                ->scopes(['expiringWithin' => [(int) config('lite-crm.notifications.expiry_warning_days', 60)]])
                ->orderBy('expires_on')
                ->get()
                ->all();
        }

        if ($this->uses('opportunities', $user)) {
            $cutoff = Date::now()->subDays((int) config('lite-crm.notifications.stale_opportunity_days', 21));

            /** @var list<Opportunity> $stale */
            $stale = LiteCrm::model(Opportunity::class)::query()
                ->where('owner_id', $user->getKey())
                ->whereNull('closed_at')
                ->where('updated_at', '<', $cutoff)
                ->whereDoesntHave('activities', fn (Builder $query) => $query->where('occurred_at', '>=', $cutoff))
                ->orderBy('updated_at')
                ->get()
                ->all();

            $currencies = Visibility::apply(LiteCrm::model(Opportunity::class)::query(), $user)
                ->whereNull('closed_at')
                ->whereNotNull('value')
                ->distinct()
                ->pluck('currency')
                ->all();

            $staleRates = Money::staleRates($currencies);
        }

        return ['documents' => $documents, 'stale' => $stale, 'stale_rates' => $staleRates];
    }

    public static function isEmpty(array $sections): bool
    {
        foreach ($sections as $section) {
            if ((is_array($section) && $section !== []) || (is_int($section) && $section > 0)) {
                return false;
            }
        }

        return true;
    }

    protected function uses(string $module, Model $user): bool
    {
        return LiteCrm::isModuleEnabled($module) && Permissions::allows($user, $module.'.view');
    }
}
