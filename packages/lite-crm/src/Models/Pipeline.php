<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property int $sort
 * @property bool $is_active
 * @property list<string>|null $visible_to_roles
 * @property array<string, mixed>|null $meta
 */
class Pipeline extends Model
{
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = ['key', 'name', 'description', 'sort', 'is_active', 'visible_to_roles', 'meta'];

    public function getTable(): string
    {
        return LiteCrm::table('pipelines');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
            'visible_to_roles' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<PipelineStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(LiteCrm::model(PipelineStage::class), 'pipeline_id')->orderBy('sort');
    }

    /**
     * Pipelines a user may work in: unrestricted ones, and restricted ones that
     * list one of the user's roles. Admins see every pipeline.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, mixed $user): Builder
    {
        if (LiteCrm::isSuperAdmin($user)) {
            return $query;
        }

        if (! $user instanceof CrmUser) {
            return $query->whereRaw('1 = 0');
        }

        return static::restrictToRoles($query, $user, 'visible_to_roles');
    }

    public function isVisibleTo(mixed $user): bool
    {
        return static::query()->whereKey($this->getKey())->visibleTo($user)->exists();
    }

    /**
     * Rows whose JSON role list is empty or contains one of the user's roles
     * (portable JSON: MariaDB/MySQL and SQLite).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function restrictToRoles(Builder $query, CrmUser $user, string $column): Builder
    {
        /** @var list<string> $roles */
        $roles = $user->roles()->pluck('name')->all();

        return $query->where(function (Builder $query) use ($roles, $column): void {
            $query->whereNull($column);

            foreach ($roles as $role) {
                $query->orWhereJsonContains($column, $role);
            }
        });
    }
}
