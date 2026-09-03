<?php

namespace Database\Seeders;

use Database\Seeders\Production\ProductionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data + the real super-admin, then the dev demo set
        // (admin login, instructors, course enroll config) - ProductionSeeder
        // now bundles both so every environment gets the same working data.
        $this->call(ProductionSeeder::class);
    }
}
