<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use LiteCrm\LiteCrm;

/**
 * Gap-free counters per name and year (quotation numbers ...). The row is
 * locked while it is incremented, so concurrent requests never get the same
 * number (MariaDB/MySQL row lock; SQLite serialises writes).
 */
class Sequence
{
    public static function next(string $name, ?int $year = null): int
    {
        $year ??= (int) Date::now()->format('Y');
        $table = LiteCrm::table('sequences');

        return DB::transaction(function () use ($name, $year, $table): int {
            $row = DB::table($table)->where('name', $name)->where('year', $year)->lockForUpdate()->first();

            if ($row === null) {
                DB::table($table)->insert([
                    'name' => $name, 'year' => $year, 'last_value' => 1,
                    'created_at' => Date::now(), 'updated_at' => Date::now(),
                ]);

                return 1;
            }

            $next = (int) $row->last_value + 1;

            DB::table($table)->where('id', $row->id)->update(['last_value' => $next, 'updated_at' => Date::now()]);

            return $next;
        });
    }
}
