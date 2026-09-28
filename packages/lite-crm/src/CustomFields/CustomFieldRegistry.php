<?php

declare(strict_types=1);

namespace LiteCrm\CustomFields;

use Illuminate\Support\Collection;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;

/**
 * Loads active custom-field definitions once per request.
 */
class CustomFieldRegistry
{
    /** @var array<string, Collection<int, CustomField>> */
    protected array $loaded = [];

    /**
     * Active definitions for an entity, in display order.
     *
     * @return Collection<int, CustomField>
     */
    public function for(string $entity): Collection
    {
        if (! isset($this->loaded[$entity])) {
            /** @var class-string<CustomField> $model */
            $model = LiteCrm::model(CustomField::class);

            $this->loaded[$entity] = $model::query()
                ->forEntity($entity)
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('label')
                ->get();
        }

        return $this->loaded[$entity];
    }

    /**
     * Definitions that apply to a record of the given type key.
     *
     * @return Collection<int, CustomField>
     */
    public function applicable(string $entity, ?string $typeKey): Collection
    {
        return $this->for($entity)->filter(fn (CustomField $field): bool => $field->appliesToType($typeKey))->values();
    }

    public function flush(): void
    {
        $this->loaded = [];
    }

    /**
     * @return array<string, string>
     */
    public static function entityOptions(): array
    {
        $options = [];

        /** @var list<string> $entities */
        $entities = config('lite-crm.custom_field_entities', []);

        foreach ($entities as $entity) {
            $key = "lite-crm::custom-fields.entities.{$entity}";
            $label = __($key);
            $options[$entity] = $label === $key ? str($entity)->replace('_', ' ')->ucfirst()->toString() : $label;
        }

        return $options;
    }
}
