<?php
// database/seeders/Course/CourseTrackSeeder.php

namespace Database\Seeders\Course;

use App\Models\CourseTrack;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseTrackSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        CourseTrack::truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $tracks = [
            // Python
            [
                'sub_category_id' => $this->getSubCategoryId('Python'),
                'name' => 'Python Fundamentals'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Python'),
                'name' => 'Python for Data Science'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Python'),
                'name' => 'Python Web Development'
            ],
            // Java
            [
                'sub_category_id' => $this->getSubCategoryId('Java'),
                'name' => 'Java Programming'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Java'),
                'name' => 'Java Enterprise'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Java'),
                'name' => 'Spring Framework'
            ],
            // JavaScript
            [
                'sub_category_id' => $this->getSubCategoryId('JavaScript'),
                'name' => 'JavaScript Basics'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('JavaScript'),
                'name' => 'Advanced JavaScript'
            ],
            // Frontend
            [
                'sub_category_id' => $this->getSubCategoryId('Frontend'),
                'name' => 'HTML & CSS'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Frontend'),
                'name' => 'React.js'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Frontend'),
                'name' => 'Vue.js'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Frontend'),
                'name' => 'Angular'
            ],
            // Backend
            [
                'sub_category_id' => $this->getSubCategoryId('Backend'),
                'name' => 'Node.js'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Backend'),
                'name' => 'Laravel'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Backend'),
                'name' => 'Django'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Backend'),
                'name' => 'Spring Boot'
            ],
            // Full Stack
            [
                'sub_category_id' => $this->getSubCategoryId('Full Stack'),
                'name' => 'MERN Stack'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Full Stack'),
                'name' => 'MEAN Stack'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Full Stack'),
                'name' => 'LAMP Stack'
            ],
        ];

        $usedSlugs = [];
        
        foreach ($tracks as $track) {
            $slug = Str::slug($track['name']);
            
            // Make slug unique if duplicate
            $counter = 1;
            $originalSlug = $slug;
            while (in_array($slug, $usedSlugs)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[] = $slug;
            
            CourseTrack::create([
                'sub_category_id' => $track['sub_category_id'],
                'name' => $track['name'],
                'slug' => $slug,
                'status' => 'active'
            ]);
        }
    }

    private function getSubCategoryId($subCategoryName)
    {
        return SubCategory::where('name', $subCategoryName)->first()->id;
    }
}