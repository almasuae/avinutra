<?php

declare(strict_types=1);

namespace LiteCrm\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Everything a fresh installation needs. Never creates users.
 */
class LiteCrmSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultLookupsSeeder::class,
        ]);
    }
}
