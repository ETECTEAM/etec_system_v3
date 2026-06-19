<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Class\ClassSeeder;
use Database\Seeders\Core\CoreSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CoreSeeder::class,
            ClassSeeder::class,
        ]);
    }
}
