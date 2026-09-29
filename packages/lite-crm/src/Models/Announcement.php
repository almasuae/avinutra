<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * A notice for the team, in Markdown, optionally pinned and limited to roles.
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property bool $pinned
 * @property list<string>|null $audience_roles
 * @property int|null $owner_id
 */
class Announcement extends Model
{
    use HasAuthors;
    use HasVisibility {
        scopeVisibleTo as protected scopeVisibleToRecords;
    }
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = ['title', 'body', 'pinned', 'audience_roles', 'owner_id'];

    protected $attributes = [
        'pinned' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('announcements');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'audience_roles' => 'array',
        ];
    }

    public static function crmModule(): string
    {
        return 'announcements';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey());
    }

    /**
     * Record visibility, and only announcements addressed to one of the user's
     * roles (or to everyone). Authors always see their own.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, mixed $user): Builder
    {
        $query = $this->scopeVisibleToRecords($query, $user);

        if ($user instanceof CrmUser && $user instanceof Model && ! LiteCrm::isSuperAdmin($user)) {
            $query->where(fn (Builder $query) => Pipeline::restrictToRoles($query, $user, 'audience_roles')
                ->orWhere('owner_id', $user->getKey()));
        }

        return $query;
    }

    /**
     * The body as safe HTML: raw HTML is stripped and unsafe links are dropped.
     */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }
}
