<?php

declare(strict_types=1);

namespace LiteCrm\Enums\Concerns;

/**
 * Labels come from lite-crm::enums.{group}.{value}.
 */
trait HasTranslatedLabel
{
    abstract protected static function translationGroup(): string;

    public function getLabel(): string
    {
        return __('lite-crm::enums.'.static::translationGroup().'.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
