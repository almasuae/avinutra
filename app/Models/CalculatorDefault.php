<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A calculator default or tax rate (v5 §E3), with its source, date and approval.
 * Until approved, the website labels it "Indicative default".
 *
 * @property int $id
 * @property string $tool
 * @property string $key
 * @property string $label
 * @property string|null $value
 * @property string|null $unit
 * @property string|null $source
 * @property Carbon|null $source_date
 * @property string|null $approved_by
 * @property Carbon|null $approved_on
 * @property string|null $notes
 */
class CalculatorDefault extends Model
{
    protected $fillable = ['tool', 'key', 'label', 'value', 'unit', 'source', 'source_date', 'approved_by', 'approved_on', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:6',
            'source_date' => 'date',
            'approved_on' => 'date',
        ];
    }

    public function isApproved(): bool
    {
        return filled($this->approved_by) && $this->approved_on !== null;
    }
}
