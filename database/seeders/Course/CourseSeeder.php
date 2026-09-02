<?php
// database/seeders/Course/CourseSeeder.php

namespace Database\Seeders\Course;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\CourseLesson;
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
        // Truncated alongside courses (not cascaded, since FK checks are off here)
        // so re-seeding doesn't leave orphaned config rows pointing at old course IDs.
        CourseEnrollConfig::truncate();
        // Same reason: lessons FK to courses, so wipe them before course IDs are reissued.
        CourseLesson::truncate();

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

            // Microsoft Office Course. Package courses bundle several subjects,
            // single-subject courses carry just their one lesson - see 'lessons'.
            [
                'track' => 'Microsoft Office',
                'title' => 'Microsoft Office',
                'level' => 'beginner',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Typing (English & Khmer)', 'description' => 'Touch-typing drills for the English (QWERTY) and Khmer (NiDA) keyboard layouts.'],
                    ['title' => 'Word 2016', 'description' => 'Documents, formatting, tables, styles, headers/footers, mail merge and printing.'],
                    ['title' => 'Excel 2016', 'description' => 'Worksheets, cell formatting, formulas and common functions, sorting/filtering and charts.'],
                    ['title' => 'Power Point 2016', 'description' => 'Slides and layouts, themes, transitions and animation, and delivering a slideshow.'],
                ],
            ],
            [
                'track' => 'Microsoft Office',
                'title' => 'Advance Microsoft Office',
                'level' => 'advanced',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Typing (English & Khmer)', 'description' => 'Touch-typing drills for the English (QWERTY) and Khmer (NiDA) keyboard layouts.'],
                    ['title' => 'Word 2016', 'description' => 'Documents, formatting, tables, styles, headers/footers, mail merge and printing.'],
                    ['title' => 'Excel 2016', 'description' => 'Worksheets, cell formatting, formulas and common functions, sorting/filtering and charts.'],
                    ['title' => 'Power Point 2016', 'description' => 'Slides and layouts, themes, transitions and animation, and delivering a slideshow.'],
                    ['title' => 'Advance Excel 2016', 'description' => 'PivotTables, lookup/reference functions, data validation, conditional logic and dashboards.'],
                ],
            ],
            [
                'track' => 'Microsoft Office',
                'title' => 'Microsoft Word',
                'level' => 'beginner',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Word 2016', 'description' => 'Documents, formatting, tables, styles, headers/footers, mail merge and printing.'],
                ],
            ],
            [
                'track' => 'Microsoft Office',
                'title' => 'Microsoft Excel',
                'level' => 'beginner',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Excel 2016', 'description' => 'Worksheets, cell formatting, formulas and common functions, sorting/filtering and charts.'],
                ],
            ],
            [
                'track' => 'Microsoft Office',
                'title' => 'Advance Excel',
                'level' => 'advanced',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Advance Excel 2016', 'description' => 'PivotTables, lookup/reference functions, data validation, conditional logic and dashboards.'],
                ],
            ],
            [
                'track' => 'Microsoft Office',
                'title' => 'Power Point',
                'level' => 'beginner',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Power Point 2016', 'description' => 'Slides and layouts, themes, transitions and animation, and delivering a slideshow.'],
                ],
            ],

            // Internship Course
            [
                'track' => 'Internship',
                'title' => 'Frontend Internship',
                'level' => 'intermediate',
                'status' => 'active',
            ],
            [
                'track' => 'Internship',
                'title' => 'Backend Internship',
                'level' => 'intermediate',
                'status' => 'active',
            ],
        ];

        $usedSlugs = [];

        // Flat placeholder so every course has a non-zero starting price; real
        // per-course numbers are set on the Enroll Config page, not seeded here.
        $placeholderPrice = 100;

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

            $createdCourse = Course::updateOrCreate(
                ['title' => $course['title']],
                [
                    'course_track_id' => $this->getTrackId($course['track']),
                    'slug' => $slug,
                    'level' => $course['level'],
                    'status' => $course['status']
                ]
            );

            CourseEnrollConfig::updateOrCreate(
                ['course_id' => $createdCourse->id, 'time_id' => null],
                ['unit_price' => $placeholderPrice, 'course_price' => $placeholderPrice, 'status' => 'open']
            );

            // Optional subject breakdown (currently only the Microsoft Office
            // courses). order_number keeps the list in the order defined above.
            foreach ($course['lessons'] ?? [] as $index => $lesson) {
                CourseLesson::updateOrCreate(
                    ['course_id' => $createdCourse->id, 'slug' => Str::slug($lesson['title'])],
                    [
                        'title' => $lesson['title'],
                        'description' => $lesson['description'] ?? null,
                        'order_number' => $index + 1,
                        'status' => 'active',
                    ]
                );
            }
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
