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
            // Frontend track
            [
                'course_track_id' => $this->getTrackId('Frontend'),
                'title' => 'HTML',
                'level' => 'beginner',
                'price' => 29.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Frontend'),
                'title' => 'CSS',
                'level' => 'beginner',
                'price' => 29.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Frontend'),
                'title' => 'JavaScript',
                'level' => 'beginner',
                'price' => 49.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Frontend'),
                'title' => 'Tailwind CSS',
                'level' => 'beginner',
                'price' => 39.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Frontend'),
                'title' => 'React.js',
                'level' => 'intermediate',
                'price' => 59.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Backend track
            [
                'course_track_id' => $this->getTrackId('Backend'),
                'title' => 'PHP',
                'level' => 'beginner',
                'price' => 39.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Backend'),
                'title' => 'Laravel',
                'level' => 'intermediate',
                'price' => 69.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Backend'),
                'title' => 'Node.js',
                'level' => 'intermediate',
                'price' => 59.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Backend'),
                'title' => 'MySQL',
                'level' => 'beginner',
                'price' => 39.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Backend'),
                'title' => 'API Development',
                'level' => 'intermediate',
                'price' => 59.99,
                'language' => 'en',
                'certificate_available' => true,
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

            Course::create([
                'course_track_id' => $course['course_track_id'],
                'title' => $course['title'],
                'slug' => $slug,
                'level' => $course['level'],
                'price' => $course['price'],
                'language' => $course['language'],
                'certificate_available' => $course['certificate_available'],
                'status' => $course['status']
            ]);
        }
    }

    private function getTrackId($trackName)
    {
        $track = CourseTrack::where('name', $trackName)->first();

        if (! $track) {
            throw new \RuntimeException("CourseTrack '{$trackName}' not found. Run CourseTrackSeeder first.");
        }

        return $track->id;
    }
}
