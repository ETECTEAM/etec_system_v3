<?php
// database/seeders/Course/CourseSeeder.php

namespace Database\Seeders\Course;

use App\Models\Course;
use App\Models\CourseTrack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Course::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $courses = [
            // Fundamental / Basic IT Track
            [
                'track' => 'Code & Network',
                'title' => 'Basic IT',
                'level' => 'beginner',
                'status' => 'active'
            ],
            // Basic Code Track
            [
                'track' => 'Basic Code',
                'title' => 'Basic / Advance C++ / OOP + Algorithm + Projects',
                'level' => 'beginner',
                'status' => 'active'
            ],
            [
                'track' => 'Basic Code',
                'title' => 'Basic / Advance Basic Python / OOP + Projects',
                'level' => 'beginner',
                'status' => 'active'
            ],

            // Web Full-Stack Course (Frontend Track)
            [
                'track' => 'Frontend Course',
                'title' => 'HTML, CSS, Bootstrap',
                'level' => 'beginner',
                'status' => 'active'
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'JavaScript + React.js, Domain Hosting',
                'level' => 'intermediate',
                'status' => 'active'
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'Web Design Frontend (Basic/Advance)',
                'level' => 'beginner',
                'status' => 'active'
            ],

            // Web Full-Stack Course (Backend Track)
            [
                'track' => 'Backend Course',
                'title' => 'PHP / MySQL + Ajax + Projects Web Backend (Basic/Advance)',
                'level' => 'intermediate',
                'status' => 'active'
            ],
            [
                'track' => 'Backend Course',
                'title' => 'PHP / MySQL + Laravel + Projects Web Backend (Basic/Advance)',
                'level' => 'advanced',
                'status' => 'active'
            ],

            // Enterprise Java Development
            [
                'track' => 'Backend Course',
                'title' => 'Java + Spring Boot (Basic/Advance - Java required)',
                'level' => 'advanced',
                'status' => 'active'
            ],

            // Mobile App Course
            [
                'track' => 'Mobile App Course',
                'title' => 'Dart + Flutter (Basic/Advance - Java Required)',
                'level' => 'intermediate',
                'status' => 'active'
            ],

            // Desktop App Course
            [
                'track' => 'Desktop App Course',
                'title' => 'C# + MySQL + Projects (Basic/Advance)',
                'level' => 'intermediate',
                'status' => 'active'
            ],
            [
                'track' => 'Desktop App Course',
                'title' => 'Java + MySQL + Projects (Basic/Advance)',
                'level' => 'intermediate',
                'status' => 'active'
            ],

            // Graphic Design Course
            [
                'track' => 'Graphic Design Course',
                'title' => 'Adobe Photoshop + UX/UI Designer',
                'level' => 'beginner',
                'status' => 'active'
            ],
            [
                'track' => 'Graphic Design Course',
                'title' => 'Adobe Photoshop + Illustrator + Projects',
                'level' => 'intermediate',
                'status' => 'active'
            ],

            // Network Course
            [
                'track' => 'Network Course',
                'title' => 'Basic Network + IT Support + Install',
                'level' => 'beginner',
                'status' => 'active'
            ],
            [
                'track' => 'Network Course',
                'title' => 'Basic Network + Basic Cyber + Tool Config',
                'level' => 'intermediate',
                'status' => 'active'
            ],
            [
                'track' => 'Network Course',
                'title' => 'Advance CISCO + Configuration',
                'level' => 'advanced',
                'status' => 'active'
            ],
        ];

        $usedSlugs = [];

        foreach ($courses as $course) {
            $slug = Str::slug($course['title']);

            // Make slug unique if duplicate
            $counter = 1;
            $originalSlug = $slug;
            while (in_array($slug, $usedSlugs)) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[] = $slug;

            Course::updateOrCreate(
                ['title' => $course['title']],
                [
                    'course_track_id' => $this->getTrackId($course['track']),
                    'slug' => $slug,
                    'level' => $course['level'],
                    'status' => $course['status']
                ]
            );
        }
    }

    private function getTrackId(?string $trackName): ?int
    {
        if (empty($trackName)) {
            return null;
        }

        $track = CourseTrack::where('name', $trackName)->first();

        if (! $track) {
            throw new \RuntimeException("CourseTrack '{$trackName}' not found. Run CourseTrackSeeder first.");
        }

        return $track->id;
    }
}
