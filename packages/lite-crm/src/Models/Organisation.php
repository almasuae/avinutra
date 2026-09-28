<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasTags;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * @property int $id
 * @property string $name
 * @property int|null $type_id
 * @property int|null $status_id
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $address
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $territory_id
 * @property string|null $source
 * @property string|null $notes
 * @property bool $permission_to_name_publicly
 * @property Carbon|null $permission_granted_on
 * @property int|null $permission_document_id
 * @property array<string, mixed>|null $custom
 * @property int|null $owner_id
 * @property-read Lookup|null $type
 */
class Organisation extends Model
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

    protected string $customFieldEntity = 'organisation';

    protected $fillable = [
        'name', 'type_id', 'status_id', 'country', 'region', 'city', 'address', 'website', 'phone', 'email',
        'territory_id', 'source', 'notes', 'permission_to_name_publicly', 'permission_granted_on',
        'permission_document_id', 'custom', 'owner_id',
    ];

    protected $attributes = [
        'permission_to_name_publicly' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('organisations');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permission_to_name_publicly' => 'boolean',
            'permission_granted_on' => 'date',
        ];
    }

    public static function crmModule(): string
    {
        return 'organisations';
    }

    /**
     * Own organisations, and those in the user's territory.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey());

        if (($territory = static::territoryOf($user)) !== null) {
            $query->orWhere('territory_id', $territory);
        }
    }

    public function customFieldTypeKey(): ?string
    {
        return $this->type?->key;
    }

    /**
     * Whether the organisation may be named on a public website: permission
     * must be recorded with a date.
     */
    public function mayBeNamedPublicly(): bool
    {
        return $this->permission_to_name_publicly && $this->permission_granted_on !== null;
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'type_id');
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'status_id');
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'territory_id');
    }

    /**
     * The evidence of permission to name the organisation publicly.
     *
     * @return BelongsTo<Document, $this>
     */
    public function permissionDocument(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Document::class), 'permission_document_id');
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(LiteCrm::model(Contact::class), 'organisation_id');
    }
}
