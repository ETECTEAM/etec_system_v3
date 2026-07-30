<?php
// database/seeders/Course/CategorySeeder.php

namespace Database\Seeders\Course;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Category::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $categories = [
            'Programming',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'Cybersecurity',
            'Cloud Computing',
            'Database',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'status' => 'active'
            ]);
        }
    }
}