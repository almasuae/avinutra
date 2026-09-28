<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LiteCrm\Models\Lookup;
use LiteCrm\Tests\TestCase;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('records who changed what, before and after', function (): void {
    $user = $this->crmUser(['admin']);
    $this->actingAs($user);

    $lookup = Lookup::query()->create(['type' => 'territory', 'key' => 'west', 'label' => 'West']);
    $lookup->update(['label' => 'West region']);

    /** @var Activity $activity */
    $activity = Activity::query()->where('subject_type', $lookup->getMorphClass())->where('event', 'updated')->latest('id')->firstOrFail();

    expect($activity->getTable())->toBe('crm_activity_log')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->properties['old']['label'])->toBe('West')
        ->and($activity->properties['attributes']['label'])->toBe('West region');
});

it('never writes MFA secrets or login times to the audit log', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->withMfa($user);
    $user->crmProfile->forceFill(['last_login_at' => now()])->save();

    $logged = Activity::query()
        ->where('subject_type', $user->crmProfile->getMorphClass())
        ->get()
        ->flatMap(fn (Activity $activity) => array_keys($activity->properties['attributes'] ?? []));

    expect($logged->all())->not->toContain('app_authentication_secret', 'app_authentication_recovery_codes', 'last_login_at');
});

it('stores MFA secrets encrypted', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->withMfa($user);

    $raw = DB::table('crm_user_profiles')->where('user_id', $user->id)->value('app_authentication_secret');

    expect($raw)->not->toBe('JBSWY3DPEHPK3PXP')
        ->and($user->refresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP');
});
