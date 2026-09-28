<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 */
class Tag extends Model
{
    use LogsCrmActivity;

    protected $fillable = ['name', 'slug', 'color'];

    public function getTable(): string
    {
        return LiteCrm::table('tags');
    }

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
