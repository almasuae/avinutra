<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Events\DocumentExpiring;
use LiteCrm\Models\Document;
use LiteCrm\Models\Setting;
use LiteCrm\Models\Task;
use LiteCrm\Notifications\DailyDigest;
use LiteCrm\Notifications\TaskAssignedNotification;
use LiteCrm\Notifications\WeeklyReport;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\SchedulerHeartbeat;
use LiteCrm\Tests\TestCase;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
});

it('sends assignment notifications in the app as well as by e-mail', function (): void {
    $assignee = $this->crmUser(['commercial']);

    Task::query()->create(['title' => 'Visit the mill', 'assignee_id' => $assignee->getKey()]);

    expect($assignee->notifications()->count())->toBe(1)
        ->and($assignee->notifications()->first()?->data['title'] ?? null)->not->toBeNull()
        ->and((new TaskAssignedNotification(Task::query()->sole()))->via($assignee))->toBe(['database', 'mail']);
});

it('sends the daily digest at the digest hour in the user\'s time zone, once a day', function (): void {
    Notification::fake();
    config(['lite-crm.notifications.digest_hour' => 8]);
    $user = $this->crmUser(['commercial'], ['time_zone' => 'Asia/Karachi']);
    Task::query()->create(['title' => 'Send the offer', 'assignee_id' => $user->getKey(), 'due_at' => now()->addHours(2)]);

    // 07:00 in Karachi (UTC+5): too early.
    $this->travelTo(now('Asia/Karachi')->setTime(7, 0)->utc());
    $this->artisan('lite-crm:send-digests')->assertSuccessful();
    Notification::assertNotSentTo($user, DailyDigest::class);

    // 08:00 in Karachi: sent, and only once that day.
    $this->travelTo(now('Asia/Karachi')->setTime(8, 5)->utc());
    $this->artisan('lite-crm:send-digests')->assertSuccessful();
    $this->artisan('lite-crm:send-digests')->assertSuccessful();

    Notification::assertSentToTimes($user, DailyDigest::class, 1);
});

it('skips the daily digest when there is nothing to report or the user opted out', function (): void {
    Notification::fake();
    config(['lite-crm.notifications.digest_hour' => 8]);
    $quiet = $this->crmUser(['commercial'], ['time_zone' => 'UTC']);
    $optedOut = $this->crmUser(['commercial'], ['time_zone' => 'UTC', 'receives_digest' => false]);
    Task::query()->create(['title' => 'Due', 'assignee_id' => $optedOut->getKey(), 'due_at' => now()]);

    $this->travelTo(now('UTC')->setTime(8, 10));
    $this->artisan('lite-crm:send-digests')->assertSuccessful();

    Notification::assertNotSentTo([$quiet, $optedOut], DailyDigest::class);
});

it('sends the weekly report and announces each expiring document once', function (): void {
    Notification::fake();
    Event::fake([DocumentExpiring::class]);
    $owner = $this->crmUser(['commercial']);
    $this->crmUser(['admin']);

    Document::query()->create([
        'title' => 'Halal certificate', 'file_path' => 'x.pdf', 'file_name' => 'x.pdf',
        'expires_on' => now()->addDays(20)->toDateString(), 'owner_id' => $owner->getKey(),
    ]);

    $this->artisan('lite-crm:send-weekly-reports')->assertSuccessful();

    Notification::assertSentToTimes($owner, WeeklyReport::class, 1);
    Event::assertDispatchedTimes(DocumentExpiring::class, 1);
});

it('schedules digests, reports, the heartbeat and audit clean-up', function (): void {
    $events = collect(app(Schedule::class)->events())->map(fn ($event): string => (string) ($event->description ?? $event->command));

    expect($events->implode("\n"))
        ->toContain('lite-crm:send-digests')
        ->toContain('lite-crm:send-weekly-reports')
        ->toContain('lite-crm:heartbeat')
        ->toContain('activitylog:clean');
});

it('records the scheduler heartbeat without filling the audit log', function (): void {
    $before = Setting::query()->count();

    (new SchedulerHeartbeat)();

    expect(app(CrmSettings::class)->get(CrmSettings::SCHEDULER_HEARTBEAT))->not->toBeNull()
        ->and(Setting::query()->count())->toBe($before + 1);

    expect(Activity::query()->where('subject_type', (new Setting)->getMorphClass())->count())->toBe(0);
});

it('reports problems with lite-crm:doctor', function (): void {
    // No admin yet: a failure, so the command exits non-zero.
    $this->artisan('lite-crm:doctor')->assertFailed();

    $admin = $this->withMfa($this->crmUser(['admin']));
    (new SchedulerHeartbeat)();

    $this->artisan('lite-crm:doctor')
        ->expectsOutputToContain(__('lite-crm::doctor.admins', ['count' => 1]))
        ->assertSuccessful();

    expect($admin)->not->toBeNull();
});
