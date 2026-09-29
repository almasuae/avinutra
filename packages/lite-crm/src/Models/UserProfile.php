<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * CRM data about a user of the host application.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $job_title
 * @property string|null $city
 * @property string|null $country
 * @property string $time_zone
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property int|null $territory_id
 * @property bool $is_active
 * @property bool $receives_digest
 * @property Carbon|null $invited_at
 * @property int|null $invited_by
 * @property Carbon|null $invitation_accepted_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $last_digest_on
 * @property string|null $app_authentication_secret
 * @property array<string>|null $app_authentication_recovery_codes
 */
class UserProfile extends Model
{
    use LogsCrmActivity;

    /** @var list<string> */
    protected array $activityLogExcept = ['last_login_at', 'last_digest_on', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected $fillable = [
        'user_id', 'job_title', 'city', 'country', 'time_zone', 'phone', 'whatsapp', 'territory_id',
        'is_active', 'receives_digest', 'invited_at', 'invited_by', 'invitation_accepted_at', 'last_login_at',
    ];

    protected $hidden = ['app_authentication_secret', 'app_authentication_recovery_codes'];

    protected $attributes = [
        'time_zone' => 'UTC',
        'is_active' => true,
        'receives_digest' => true,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('user_profiles');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'receives_digest' => 'boolean',
            'invited_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_digest_on' => 'date',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'user_id');
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'territory_id');
    }

    public function isInvitationPending(): bool
    {
        return $this->invited_at !== null && $this->invitation_accepted_at === null;
    }
}
