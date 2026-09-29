<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SEO title and description for a public page, keyed by its route name.
 * Empty fields fall back to the page's own defaults.
 *
 * @property int $id
 * @property string $route_name
 * @property string|null $title
 * @property string|null $description
 */
class PageSeo extends Model
{
    protected $table = 'page_seo';

    protected $fillable = ['route_name', 'title', 'description'];

    public static function forRoute(?string $routeName): ?self
    {
        if ($routeName === null) {
            return null;
        }

        try {
            return static::query()->where('route_name', $routeName)->first();
        } catch (\Throwable) {
            return null; // Before migrations (fresh install).
        }
    }
}
