<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * A recorded team decision. Append-only for everyone but Admins (see DecisionPolicy).
 *
 * @property int $id
 * @property Carbon $decided_on
 * @property string $title
 * @property string $decision
 * @property string|null $rationale
 * @property string|null $decided_by
 * @property string|null $related_type
 * @property int|null $related_id
 * @property int|null $owner_id
 * @property-read Model|null $related
 */
class Decision extends Model
{
    use HasAuthors;
    use HasVisibility;
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = ['decided_on', 'title', 'decision', 'rationale', 'decided_by', 'related_type', 'related_id', 'owner_id'];

    public function getTable(): string
    {
        return LiteCrm::table('decisions');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_on' => 'date',
        ];
    }

    public static function crmModule(): string
    {
        return 'decisions';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey());
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
