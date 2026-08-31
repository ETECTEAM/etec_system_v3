<?php

namespace Database\Seeders;

use Database\Seeders\Dev\DevSeeder;
use Database\Seeders\Production\ProductionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference / lookup data + the real super-admin. Runs everywhere.
        $this->call(ProductionSeeder::class);

        // Fake users + demo data. Skipped when APP_ENV=production so a real
        // deploy never gets throwaway accounts. Can still be run explicitly:
        //   php artisan db:seed --class="Database\Seeders\Dev\DevSeeder"
        if (! app()->isProduction()) {
            $this->call(DevSeeder::class);
        }
    }
}
