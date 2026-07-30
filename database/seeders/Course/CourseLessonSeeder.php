<?php
// database/seeders/Course/CourseLessonSeeder.php

namespace Database\Seeders\Course;

use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseLessonSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        CourseLesson::truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $lessons = [
            // React.js Course Lessons
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'Introduction to React.js',
                'order_number' => 1,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React Components and Props',
                'order_number' => 2,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React State Management with Hooks',
                'order_number' => 3,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React Router: Navigation in React',
                'order_number' => 4,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'State Management with Redux',
                'order_number' => 5,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React Forms and Validation',
                'order_number' => 6,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'API Integration in React',
                'order_number' => 7,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React Performance Optimization',
                'order_number' => 8,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'Testing React Applications',
                'order_number' => 9,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js'),
                'title' => 'React Project: Build a Full Application',
                'order_number' => 10,
                'status' => 'active'
            ],
        ];

        $usedSlugs = [];
        
        foreach ($lessons as $lesson) {
            $slug = Str::slug($lesson['title']);
            
            // Make slug unique if duplicate
            $counter = 1;
            $originalSlug = $slug;
            while (in_array($slug, $usedSlugs)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[] = $slug;
            
            CourseLesson::create([
                'course_id' => $lesson['course_id'],
                'title' => $lesson['title'],
                'order_number' => $lesson['order_number'],
                'status' => $lesson['status']
            ]);
        }
    }

    private function getCourseId($courseTitle)
    {
        $course = Course::where('title', $courseTitle)->first();

        if (! $course) {
            throw new \RuntimeException("Course '{$courseTitle}' not found. Run CourseSeeder first.");
        }

        return $course->id;
    }
}