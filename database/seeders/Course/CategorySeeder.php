<?php
// database/seeders/Course/CategorySeeder.php

namespace Database\Seeders\Course;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'Web Development',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'Cybersecurity',
            'Cloud Computing',
            'Database',
        ];

        $usedSlugs = [];
        
        foreach ($categories as $category) {
            $slug = Str::slug($category);
            
            // Make slug unique if duplicate
            $counter = 1;
            $originalSlug = $slug;
            while (in_array($slug, $usedSlugs)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[] = $slug;
            
            Category::create([
                'name' => $category,
                'slug' => $slug,
                'status' => 'active'
            ]);
        }
    }
}