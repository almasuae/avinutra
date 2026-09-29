<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Events\EnquiryAssigned;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * An incoming request (website form, API, or logged by hand), handled in the
 * CRM inbox and converted into an organisation and contact.
 *
 * @property int $id
 * @property int|null $type_id
 * @property EnquiryStatus $status
 * @property string|null $name
 * @property string|null $company
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $country
 * @property string|null $city
 * @property string|null $message
 * @property array<string, mixed>|null $payload
 * @property string|null $source_url
 * @property bool $consent_given
 * @property Carbon|null $consent_at
 * @property int|null $assignee_id
 * @property Carbon|null $first_response_at
 * @property int|null $organisation_id
 * @property int|null $contact_id
 * @property Carbon|null $converted_at
 * @property int|null $converted_by
 * @property string|null $spam_reason
 * @property string $channel
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property int|null $owner_id
 * @property Carbon $created_at
 * @property-read Lookup|null $type
 * @property-read Organisation|null $organisation
 * @property-read Contact|null $contact
 */
class Enquiry extends Model
{
    use HasAuthors;
    use HasRelatedRecords;
    use HasVisibility;
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    /** @var list<string> */
    protected array $activityLogExcept = ['ip_hash', 'user_agent'];

    protected $fillable = [
        'type_id', 'status', 'name', 'company', 'email', 'phone', 'country', 'city', 'message', 'payload',
        'source_url', 'consent_given', 'consent_at', 'assignee_id', 'owner_id',
    ];

    protected $attributes = [
        'status' => 'new',
        'channel' => 'form',
        'consent_given' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('enquiries');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnquiryStatus::class,
            'payload' => 'array',
            'consent_given' => 'boolean',
            'consent_at' => 'datetime',
            'first_response_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Enquiry $enquiry): void {
            // Assigning a new enquiry moves it to "Assigned".
            if ($enquiry->isDirty('assignee_id') && $enquiry->assignee_id !== null && $enquiry->status === EnquiryStatus::New) {
                $enquiry->status = EnquiryStatus::Assigned;
            }

            if ($enquiry->isDirty('status') && $enquiry->status->isResponse() && $enquiry->first_response_at === null) {
                $enquiry->first_response_at = Date::now();
            }
        });

        static::updated(function (Enquiry $enquiry): void {
            if ($enquiry->assignee_id !== null && $enquiry->wasChanged('assignee_id')) {
                EnquiryAssigned::dispatch($enquiry);
            }
        });
    }

    public static function crmModule(): string
    {
        return 'enquiries';
    }

    /**
     * Without "view_all" (e.g. Partners): enquiries assigned to or owned by the user.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('assignee_id', $user->getKey())->orWhere('owner_id', $user->getKey());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInbox(Builder $query): Builder
    {
        return $query->where('status', '!=', EnquiryStatus::Spam->value);
    }

    /**
     * Records the first response (an activity logged, or work started).
     */
    public function markResponded(): void
    {
        if ($this->first_response_at === null) {
            $this->first_response_at = Date::now();
            $this->save();
        }
    }

    public function displayName(): string
    {
        return collect([$this->name, $this->company])->filter()->implode(', ') ?: (string) ($this->email ?? '#'.$this->id);
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'type_id');
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'assignee_id');
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'organisation_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Contact::class), 'contact_id');
    }
}
