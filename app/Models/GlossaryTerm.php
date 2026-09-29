<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $term
 * @property string $slug
 * @property string $definition
 * @property bool $is_published
 */
class GlossaryTerm extends Model
{
    protected $fillable = ['term', 'slug', 'definition', 'is_published'];

    protected static function booted(): void
    {
        static::saving(function (GlossaryTerm $term): void {
            if (blank($term->slug)) {
                $term->slug = Str::slug($term->term) ?: 'term';
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /**
     * @param  Builder<GlossaryTerm>  $query
     * @return Builder<GlossaryTerm>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
