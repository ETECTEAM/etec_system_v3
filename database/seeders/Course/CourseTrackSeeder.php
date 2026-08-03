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
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Frontend'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Backend'
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
        $subCategory = SubCategory::where('name', $subCategoryName)->first();

        if (! $subCategory) {
            throw new \RuntimeException("SubCategory '{$subCategoryName}' not found. Run SubCategorySeeder first.");
        }

        return $subCategory->id;
    }
}
