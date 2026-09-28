<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Tag;

/**
 * Adds CRM tags to an entity.
 */
trait HasTags
{
    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(LiteCrm::model(Tag::class), 'taggable', LiteCrm::table('taggables'), 'taggable_id', 'tag_id');
    }
}
