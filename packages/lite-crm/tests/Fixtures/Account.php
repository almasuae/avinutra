<?php

declare(strict_types=1);

namespace LiteCrm\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasTags;

/**
 * Stands in for an organisation until the entity modules exist.
 *
 * @property int $id
 * @property string $name
 * @property string|null $type_key
 * @property array<string, mixed>|null $custom
 */
class Account extends Model
{
    use HasCustomFields;
    use HasTags;

    protected string $customFieldEntity = 'organisation';

    protected $table = 'test_accounts';

    protected $fillable = ['name', 'type_key', 'custom'];

    public function customFieldTypeKey(): ?string
    {
        return $this->type_key;
    }
}
