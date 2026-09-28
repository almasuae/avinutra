<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * One entry of a simple list (organisation types, territories, lost reasons ...).
 * The list is identified by "type"; "key" is stable and used by presets and
 * custom-field conditions, "label" is what users see.
 *
 * @property int $id
 * @property string $type
 * @property string $key
 * @property string $label
 * @property int $sort
 * @property bool $is_active
 * @property array<string, mixed>|null $meta
 */
class Lookup extends Model
{
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = ['type', 'key', 'label', 'sort', 'is_active', 'meta'];

    public function getTable(): string
    {
        return LiteCrm::table('lookups');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Active entries of a list for a select field, in display order.
     *
     * @param  'id'|'key'  $valueColumn
     * @return array<int|string, string>
     */
    public static function options(string $type, string $valueColumn = 'id'): array
    {
        /** @var array<int|string, string> $options */
        $options = static::query()
            ->ofType($type)
            ->active()
            ->orderBy('sort')
            ->orderBy('label')
            ->pluck('label', $valueColumn)
            ->all();

        return $options;
    }

    /**
     * Human-readable name of a lookup type, e.g. "organisation_type" → "Organisation types".
     */
    public static function typeLabel(string $type): string
    {
        $key = "lite-crm::lookups.types.{$type}";
        $label = __($key);

        return $label === $key ? str($type)->replace('_', ' ')->ucfirst()->toString() : $label;
    }
}
