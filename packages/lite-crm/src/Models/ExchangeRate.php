<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * Units of the base currency for one unit of "currency", from "valid_from" on.
 *
 * @property int $id
 * @property string $currency
 * @property string $rate
 * @property Carbon $valid_from
 * @property string|null $source
 */
class ExchangeRate extends Model
{
    use LogsCrmActivity;

    protected $fillable = ['currency', 'rate', 'valid_from', 'source'];

    public function getTable(): string
    {
        return LiteCrm::table('exchange_rates');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'valid_from' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ExchangeRate $rate) => $rate->setAttribute('created_by', Auth::id()));
        static::saving(fn (ExchangeRate $rate) => $rate->setAttribute('updated_by', Auth::id()));
    }
}
