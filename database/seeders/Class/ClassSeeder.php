<?php

namespace Database\Seeders\Class;

use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ClassTypeSeeder::class,
            ClassCategorySeeder::class,
        ]);
    }
}
