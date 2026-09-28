<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * @property int $id
 * @property int $pipeline_id
 * @property string $key
 * @property string $name
 * @property int $probability
 * @property bool $is_won
 * @property bool $is_lost
 * @property int $sort
 */
class PipelineStage extends Model
{
    use LogsCrmActivity;

    protected $fillable = ['pipeline_id', 'key', 'name', 'probability', 'is_won', 'is_lost', 'sort'];

    public function getTable(): string
    {
        return LiteCrm::table('pipeline_stages');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'probability' => 'integer',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Pipeline, $this>
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Pipeline::class), 'pipeline_id');
    }
}
