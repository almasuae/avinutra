<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Permissions;
use LiteCrm\Support\Visibility;

/**
 * A file on the private disk (certificate, specification, contract ...).
 * Served only through short-lived signed links after a permission check.
 *
 * @property int $id
 * @property string|null $documentable_type
 * @property int|null $documentable_id
 * @property string $title
 * @property int|null $type_id
 * @property string $disk
 * @property string $file_path
 * @property string $file_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property string|null $issuer
 * @property string|null $certificate_number
 * @property Carbon|null $issued_on
 * @property Carbon|null $expires_on
 * @property bool $verified
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property string|null $verification_method
 * @property bool $confidential
 * @property string|null $notes
 * @property int|null $owner_id
 * @property-read Model|null $documentable
 */
class Document extends Model
{
    use HasAuthors;
    use HasVisibility {
        scopeVisibleTo as protected scopeVisibleToRecords;
    }
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = [
        'documentable_type', 'documentable_id', 'title', 'type_id', 'file_path', 'file_name', 'issuer',
        'certificate_number', 'issued_on', 'expires_on', 'confidential', 'notes', 'owner_id',
    ];

    protected $attributes = [
        'verified' => false,
        'confidential' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('documents');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'verified' => 'boolean',
            'verified_at' => 'datetime',
            'confidential' => 'boolean',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            if (blank($document->disk)) {
                $document->disk = (string) config('lite-crm.documents.disk', 'local');
            }

            if ($document->isDirty('file_path') && filled($document->file_path)) {
                $storage = Storage::disk($document->disk);

                if ($storage->exists($document->file_path)) {
                    $document->mime_type = $storage->mimeType($document->file_path) ?: null;
                    $document->size = $storage->size($document->file_path);
                }

                if (blank($document->file_name)) {
                    $document->file_name = basename($document->file_path);
                }
            }
        });
    }

    public static function crmModule(): string
    {
        return 'documents';
    }

    /**
     * Own documents, and documents on records the user may see.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHasMorph('documentable', LiteCrm::recordModels(), fn (Builder $records) => Visibility::apply($records, $user));
    }

    /**
     * Visibility as for other records, and confidential documents only for their
     * owner and users allowed to see confidential documents.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, mixed $user): Builder
    {
        $query = $this->scopeVisibleToRecords($query, $user);

        if ($user instanceof Model && ! Permissions::allows($user, 'documents.view_confidential')) {
            $query->where(fn (Builder $query) => $query
                ->where('confidential', false)
                ->orWhere('owner_id', $user->getKey()));
        }

        return $query;
    }

    /**
     * A download link valid for a few minutes. The route also checks access.
     */
    public function temporaryDownloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'filament.'.LiteCrm::panelId().'.lite-crm.documents.download',
            Date::now()->addMinutes((int) config('lite-crm.documents.download_link_minutes', 5)),
            ['document' => $this->getKey()],
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast() && ! $this->expires_on->isToday();
    }

    public function expiresWithin(int $days): bool
    {
        return $this->expires_on !== null && ! $this->isExpired() && $this->expires_on->lte(Date::today()->addDays($days));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expires_on')
            ->whereDate('expires_on', '>=', Date::today())
            ->whereDate('expires_on', '<=', Date::today()->addDays($days));
    }

    public function markVerified(Model $verifier, string $method): void
    {
        $this->forceFill([
            'verified' => true,
            'verified_by' => $verifier->getKey(),
            'verified_at' => Date::now(),
            'verification_method' => $method,
        ])->save();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'verified_by');
    }
}
