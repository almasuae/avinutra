<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\ConsentBasis;
use LiteCrm\Enums\PreferredChannel;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasTags;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * @property int $id
 * @property int|null $organisation_id
 * @property string|null $first_name
 * @property string $last_name
 * @property string|null $job_title
 * @property string|null $department
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property PreferredChannel|null $preferred_channel
 * @property list<string>|null $languages
 * @property string|null $time_zone
 * @property ConsentBasis|null $consent_basis
 * @property Carbon|null $consent_date
 * @property string|null $notes
 * @property array<string, mixed>|null $custom
 * @property int|null $owner_id
 * @property-read string $name
 * @property-read Organisation|null $organisation
 */
class Contact extends Model
{
    use HasAuthors;
    use HasCustomFields;
    use HasRelatedRecords;
    use HasTags;
    use HasVisibility;
    use LogsCrmActivity {
        // "activities" are CRM interactions; the audit trail is auditLog().
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    protected string $customFieldEntity = 'contact';

    protected $fillable = [
        'organisation_id', 'first_name', 'last_name', 'job_title', 'department', 'email', 'phone', 'whatsapp',
        'preferred_channel', 'languages', 'time_zone', 'consent_basis', 'consent_date', 'notes', 'custom', 'owner_id',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('contacts');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_channel' => PreferredChannel::class,
            'consent_basis' => ConsentBasis::class,
            'consent_date' => 'date',
            'languages' => 'array',
        ];
    }

    public static function crmModule(): string
    {
        return 'contacts';
    }

    /**
     * Own contacts, and contacts of organisations the user may see.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('organisation', fn (Builder $organisations) => Visibility::apply($organisations, $user));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim(($this->first_name ?? '').' '.$this->last_name));
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'organisation_id');
    }
}
