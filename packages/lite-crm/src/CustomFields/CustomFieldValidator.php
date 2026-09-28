<?php

declare(strict_types=1);

namespace LiteCrm\CustomFields;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LiteCrm\Models\CustomField;

/**
 * Validates and normalises custom-field values against their definitions.
 * Used by model saving (imports, API, code) as well as by Filament forms.
 */
class CustomFieldValidator
{
    public function __construct(protected CustomFieldRegistry $registry) {}

    /**
     * Laravel validation rules for the fields that apply to a record.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(string $entity, ?string $typeKey = null, string $prefix = 'custom.'): array
    {
        $rules = [];

        foreach ($this->registry->applicable($entity, $typeKey) as $field) {
            foreach (static::rulesFor($field) as $suffix => $fieldRules) {
                $rules[$prefix.$field->key.$suffix] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Rules for one field. The key '' holds the field's own rules; '.*' holds
     * the rules for each item of a multiselect.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(CustomField $field): array
    {
        $presence = $field->required ? 'required' : 'nullable';

        return match ($field->type) {
            CustomFieldType::Text => ['' => [$presence, 'string', 'max:255']],
            CustomFieldType::Textarea => ['' => [$presence, 'string', 'max:65535']],
            CustomFieldType::Number => ['' => [$presence, 'integer']],
            CustomFieldType::Decimal, CustomFieldType::Currency => ['' => [$presence, 'numeric']],
            CustomFieldType::Percentage => ['' => [$presence, 'numeric', 'min:0', 'max:100']],
            CustomFieldType::Date => ['' => [$presence, 'date']],
            CustomFieldType::Boolean => ['' => [$presence, 'boolean']],
            CustomFieldType::Url => ['' => [$presence, 'url', 'max:2048']],
            CustomFieldType::Select => ['' => [$presence, Rule::in(array_keys($field->optionMap()))]],
            CustomFieldType::Multiselect => [
                '' => [$presence, 'array'],
                '.*' => [Rule::in(array_keys($field->optionMap()))],
            ],
        };
    }

    /**
     * Validate values and cast them to their natural types. Keys that belong to
     * no applicable definition are kept unchanged, so switching a field off or
     * changing a record's type never destroys data.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(string $entity, array $values, ?string $typeKey = null): array
    {
        $fields = $this->registry->applicable($entity, $typeKey);

        Validator::make(['custom' => $values], $this->rules($entity, $typeKey), [], $this->attributeNames($fields->all()))
            ->validate();

        foreach ($fields as $field) {
            if (array_key_exists($field->key, $values)) {
                $values[$field->key] = static::cast($field, $values[$field->key]);
            }
        }

        return $values;
    }

    public static function cast(CustomField $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field->type) {
            CustomFieldType::Number => (int) $value,
            CustomFieldType::Decimal, CustomFieldType::Currency, CustomFieldType::Percentage => (float) $value,
            CustomFieldType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            CustomFieldType::Multiselect => array_values(array_map('strval', (array) $value)),
            default => is_scalar($value) ? (string) $value : $value,
        };
    }

    /**
     * @param  array<int, CustomField>  $fields
     * @return array<string, string>
     */
    protected function attributeNames(array $fields): array
    {
        $names = [];

        foreach ($fields as $field) {
            $names['custom.'.$field->key] = $field->label;
        }

        return $names;
    }
}
