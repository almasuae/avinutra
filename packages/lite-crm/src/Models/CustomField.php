<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldType;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * Definition of an extra field on an entity. Values live in the entity's
 * "custom" JSON column under this definition's key.
 *
 * @property int $id
 * @property string $entity
 * @property string $key
 * @property string $label
 * @property CustomFieldType $type
 * @property array<int, array{value: string, label: string}>|null $options
 * @property string|null $section
 * @property string|null $help_text
 * @property bool $required
 * @property list<string>|null $visible_for_types
 * @property bool $show_in_table
 * @property bool $filterable
 * @property int $sort
 * @property bool $is_active
 */
class CustomField extends Model
{
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = [
        'entity', 'key', 'label', 'type', 'options', 'section', 'help_text', 'required',
        'visible_for_types', 'show_in_table', 'filterable', 'sort', 'is_active',
    ];

    protected $attributes = [
        'required' => false,
        'show_in_table' => false,
        'filterable' => false,
        'sort' => 0,
        'is_active' => true,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('custom_fields');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomFieldType::class,
            'options' => 'array',
            'required' => 'boolean',
            'visible_for_types' => 'array',
            'show_in_table' => 'boolean',
            'filterable' => 'boolean',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => app(CustomFieldRegistry::class)->flush();

        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForEntity(Builder $query, string $entity): Builder
    {
        return $query->where('entity', $entity);
    }

    /**
     * The select options as value => label.
     *
     * @return array<string, string>
     */
    public function optionMap(): array
    {
        $map = [];

        foreach ($this->options ?? [] as $option) {
            $map[(string) $option['value']] = (string) $option['label'];
        }

        return $map;
    }

    /**
     * Whether the field applies to a record of the given type key (e.g. an
     * organisation type). A field without conditions applies to every record.
     */
    public function appliesToType(?string $typeKey): bool
    {
        if (blank($this->visible_for_types)) {
            return true;
        }

        return $typeKey !== null && in_array($typeKey, $this->visible_for_types, true);
    }
}
