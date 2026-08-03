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
                'name' => 'Web Development'
            ],
            [
                'category_id' => $this->getCategoryId('Programming'),
                'name' => 'Mobile Development'
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
        $category = Category::where('name', $categoryName)->first();

        if (! $category) {
            throw new \RuntimeException("Category '{$categoryName}' not found. Run CategorySeeder first.");
        }

        return $category->id;
    }
}   