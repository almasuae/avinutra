<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use LiteCrm\CustomFields\CustomFieldValidator;

/**
 * Stores custom-field values in the model's "custom" JSON column and validates
 * them against the definitions whenever the model is saved.
 *
 * Using models set `protected string $customFieldEntity = '...';` and may
 * override customFieldTypeKey() to return the record's type key (e.g. the
 * organisation type), which drives "visible_for_types".
 */
trait HasCustomFields
{
    public static function bootHasCustomFields(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            if (! $model->isDirty('custom') && $model->exists) {
                return;
            }

            $model->custom = app(CustomFieldValidator::class)->validate(
                $model->customFieldEntity(),
                $model->custom ?? [],
                $model->customFieldTypeKey(),
            );
        });
    }

    public function initializeHasCustomFields(): void
    {
        $this->mergeCasts(['custom' => 'array']);
    }

    public function customFieldEntity(): string
    {
        return $this->customFieldEntity;
    }

    public function customFieldTypeKey(): ?string
    {
        return null;
    }

    public function getCustomValue(string $key, mixed $default = null): mixed
    {
        return $this->custom[$key] ?? $default;
    }

    public function setCustomValue(string $key, mixed $value): static
    {
        $custom = $this->custom ?? [];
        $custom[$key] = $value;
        $this->custom = $custom;

        return $this;
    }
}
