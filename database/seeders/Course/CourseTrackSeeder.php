<?php
// database/seeders/Course/CourseTrackSeeder.php

namespace Database\Seeders\Course;

use App\Models\ClassType;
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
                'sub_category_id' => $this->getSubCategoryId('Basic IT'),
                'name' => 'Code & Network'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Programming Fundamentals'),
                'name' => 'Basic Code'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Web Full-Stack Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Enterprise Java Development'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Mobile Development'),
                'name' => 'Mobile App Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Desktop Development'),
                'name' => 'Desktop App Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Graphic & UI/UX Design'),
                'name' => 'Graphic Design Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Network & Systems'),
                'name' => 'Network Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Frontend Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Web Development'),
                'name' => 'Backend Course'
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Office Skills'),
                'name' => 'Microsoft Office',
                // Enroll Config for this track's courses uses the "Microsoft
                // Office" Class Type's schedules, not the default set.
                'class_type' => 'Microsoft Office',
            ],
            [
                'sub_category_id' => $this->getSubCategoryId('Internship'),
                'name' => 'Internship',
                'class_type' => 'Internship',
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

            CourseTrack::updateOrCreate(
                ['name' => $track['name']],
                [
                    'sub_category_id' => $track['sub_category_id'],
                    'class_type_id' => $this->getClassTypeId($track['class_type'] ?? null),
                    'slug' => $slug,
                    'status' => 'active'
                ]
            );
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

    // Null when the track has no 'class_type' key - it then falls back to the
    // default schedule set in Enroll Config.
    private function getClassTypeId(?string $typeName): ?int
    {
        if ($typeName === null) {
            return null;
        }

        $classType = ClassType::where('type_name', $typeName)->first();

        if (! $classType) {
            throw new \RuntimeException("ClassType '{$typeName}' not found. Run ClassTypeSeeder first.");
        }

        return $classType->class_type_id;
    }
}
