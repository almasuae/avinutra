<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * A CRM-wide setting edited by Admins (key + JSON value).
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    use LogsCrmActivity;

    protected $fillable = ['key', 'value'];

    public function getTable(): string
    {
        return LiteCrm::table('settings');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
