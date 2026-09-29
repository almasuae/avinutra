<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Knowledge Centre categories (Content Blueprint v3 §7.9).
 */
enum ArticleCategory: string implements HasLabel
{
    case PoultryNutrition = 'poultry-nutrition';
    case FeedIngredients = 'feed-ingredients';
    case FeedEconomics = 'feed-economics';
    case IngredientQuality = 'ingredient-quality';
    case MarketWatch = 'market-watch';

    public function getLabel(): string
    {
        return match ($this) {
            self::PoultryNutrition => 'Poultry Nutrition',
            self::FeedIngredients => 'Feed Ingredients',
            self::FeedEconomics => 'Feed Economics',
            self::IngredientQuality => 'Ingredient Quality',
            self::MarketWatch => 'Market Watch',
        };
    }
}
