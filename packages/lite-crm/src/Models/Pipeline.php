<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property int $sort
 * @property bool $is_active
 * @property array<string, mixed>|null $meta
 */
class Pipeline extends Model
{
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = ['key', 'name', 'description', 'sort', 'is_active', 'meta'];

    public function getTable(): string
    {
        return LiteCrm::table('pipelines');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<PipelineStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(LiteCrm::model(PipelineStage::class), 'pipeline_id')->orderBy('sort');
    }
}
