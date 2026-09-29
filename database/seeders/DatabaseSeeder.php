<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LiteCrm\Database\Seeders\LiteCrmSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: CRM roles, permissions and neutral lists,
     * then AviNutra's preset and enquiry mailboxes.
     *
     * Never seed users here: there is no default account and no known password.
     * The first admin is created only with `php artisan lite-crm:create-admin {email}`.
     */
    public function run(): void
    {
        $this->call([LiteCrmSeeder::class, AviNutraSeeder::class]);
    }
}
