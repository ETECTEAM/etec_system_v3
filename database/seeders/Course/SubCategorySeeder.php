<?php
// database/seeders/Course/SubCategorySeeder.php

namespace Database\Seeders\Course;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        SubCategory::truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $subCategories = [
            // Programming
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Python'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Java'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'JavaScript'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'C++'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'C#'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'PHP'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Ruby'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Go'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Rust'
            ],

            // Web Development
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Frontend'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Backend'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Full Stack'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'React'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Vue.js'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Angular'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Laravel'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Node.js'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Django'
            ],
            [
                'category_id' => $this->getCategoryId('Web Development'),
                'name' => 'Spring Boot'
            ],
        ];

        $usedSlugs = [];
        
        foreach ($subCategories as $subCategory) {
            $slug = Str::slug($subCategory['name']);
            
            // Make slug unique if duplicate
            $counter = 1;
            $originalSlug = $slug;
            while (in_array($slug, $usedSlugs)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[] = $slug;
            
            SubCategory::create([
                'category_id' => $subCategory['category_id'],
                'name' => $subCategory['name'],
                'slug' => $slug,
                'status' => 'active'
            ]);
        }
    }

    private function getCategoryId($categoryName)
    {
        return Category::where('name', $categoryName)->first()->id;
    }
}