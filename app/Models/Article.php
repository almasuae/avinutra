<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Support\ExternalLinks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A Knowledge Centre article (v3 §7.9). Only published articles are shown.
 * Articles written under a person's name stay unpublished until that person
 * has authored or reviewed them; company content shows "AviNutra Technical Team".
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ArticleCategory $category
 * @property ArticleStatus $status
 * @property string|null $summary
 * @property string|null $body
 * @property string|null $outline
 * @property int|null $author_id
 * @property int|null $reviewer_id
 * @property list<array{title?: string, url?: string, date?: string}>|null $sources
 * @property string|null $related_route
 * @property Carbon|null $last_reviewed_on
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property-read TeamProfile|null $author
 * @property-read TeamProfile|null $reviewer
 */
class Article extends Model
{
    use SoftDeletes;

    public const COMPANY_AUTHOR = 'AviNutra Technical Team';

    protected $fillable = [
        'title', 'slug', 'category', 'status', 'summary', 'body', 'outline', 'author_id', 'reviewer_id',
        'sources', 'related_route', 'last_reviewed_on', 'published_at',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title) ?: 'article';
            }

            if ($article->status === ArticleStatus::Published && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'status' => ArticleStatus::class,
            'sources' => 'array',
            'last_reviewed_on' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @return BelongsTo<TeamProfile, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(TeamProfile::class, 'author_id');
    }

    /**
     * @return BelongsTo<TeamProfile, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(TeamProfile::class, 'reviewer_id');
    }

    /**
     * The byline: a person only when their profile is public (consent on file).
     */
    public function authorName(): string
    {
        return $this->author?->isPublic() ? $this->author->name : self::COMPANY_AUTHOR;
    }

    public function reviewerName(): ?string
    {
        return $this->reviewer?->isPublic() ? $this->reviewer->name : null;
    }

    /**
     * The body as HTML. Raw HTML in the Markdown is stripped.
     */
    public function bodyHtml(): string
    {
        return ExternalLinks::process(Str::markdown((string) $this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }
}
