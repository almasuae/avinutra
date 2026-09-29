<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A public profile of an adviser, employee or consultant. It appears on the
 * website only when published AND written consent is on file (v3 §7.2).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property TeamRole $role_type
 * @property string|null $job_title
 * @property string|null $qualification
 * @property string|null $university
 * @property int|null $qualification_year
 * @property int|null $experience_years
 * @property string|null $specialisations
 * @property string|null $bio
 * @property list<array{title?: string, url?: string}>|null $publications
 * @property string|null $languages
 * @property string|null $photo_path
 * @property bool $consent_on_file
 * @property Carbon|null $consent_date
 * @property bool $is_published
 * @property int $sort
 */
class TeamProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'role_type', 'job_title', 'qualification', 'university', 'qualification_year',
        'experience_years', 'specialisations', 'bio', 'publications', 'languages', 'photo_path',
        'consent_on_file', 'consent_date', 'is_published', 'sort',
    ];

    protected static function booted(): void
    {
        static::saving(function (TeamProfile $profile): void {
            if (blank($profile->slug)) {
                $profile->slug = Str::slug($profile->name) ?: 'profile';
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_type' => TeamRole::class,
            'publications' => 'array',
            'consent_on_file' => 'boolean',
            'consent_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Profiles that may be shown publicly: published, with dated consent on file.
     *
     * @param  Builder<TeamProfile>  $query
     * @return Builder<TeamProfile>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('consent_on_file', true)->whereNotNull('consent_date');
    }

    public function isPublic(): bool
    {
        return $this->is_published && $this->consent_on_file && $this->consent_date !== null;
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function authoredArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function reviewedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewer_id');
    }
}
