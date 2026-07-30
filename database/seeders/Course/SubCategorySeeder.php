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

            // Web Development (nested under Programming as its own sub-category)
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Web Development'
            ],

            // Previously under a standalone "Web Development" category; re-parented
            // under Programming so this data isn't lost when that category was removed.
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Frontend'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Backend'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Full Stack'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'React'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Vue.js'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Angular'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Laravel'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Node.js'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Django'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
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