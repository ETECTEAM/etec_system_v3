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
            // Programming Category
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Programming Fundamentals'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Web Development'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Mobile Development'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Desktop Development'
            ],
            // Graphic Design Category
            [
                'category_id' => $this->getCategoryId('Graphic Design'),
                'name' => 'Graphic & UI/UX Design'
            ],
            // Networking Category
            [
                'category_id' => $this->getCategoryId('Networking'),
                'name' => 'Network & Systems'
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
            
            SubCategory::updateOrCreate(
                ['name' => $subCategory['name']],
                [
                    'category_id' => $subCategory['category_id'],
                    'slug' => $slug,
                    'status' => 'active'
                ]
            );
        }
    }

    private function getCategoryId($categoryName)
    {
        $category = Category::where('name', $categoryName)->first();

        if (! $category) {
            throw new \RuntimeException("Category '{$categoryName}' not found. Run CategorySeeder first.");
        }

        return $category->id;
    }
}   