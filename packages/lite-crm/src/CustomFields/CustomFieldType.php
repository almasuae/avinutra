<?php

declare(strict_types=1);

namespace LiteCrm\CustomFields;

enum CustomFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Decimal = 'decimal';
    case Currency = 'currency';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Percentage = 'percentage';
    case Url = 'url';

    public function label(): string
    {
        return __("lite-crm::custom-fields.types.{$this->value}");
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Multiselect], true);
    }

    public function isNumeric(): bool
    {
        return in_array($this, [self::Number, self::Decimal, self::Currency, self::Percentage], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
