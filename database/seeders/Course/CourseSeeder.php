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
            // Python Courses
            [
                'course_track_id' => $this->getTrackId('Python Fundamentals'),
                'title' => 'Python Programming: From Zero to Hero',
                'level' => 'beginner',
                'price' => 59.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Python for Data Science'),
                'title' => 'Python for Data Science & Machine Learning',
                'level' => 'intermediate',
                'price' => 99.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Python Web Development'),
                'title' => 'Python Web Development with Django',
                'level' => 'intermediate',
                'price' => 89.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Java Courses
            [
                'course_track_id' => $this->getTrackId('Java Programming'),
                'title' => 'Java Masterclass: Complete Java Programming',
                'level' => 'beginner',
                'price' => 69.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Java Enterprise'),
                'title' => 'Java Enterprise Edition (JEE) Masterclass',
                'level' => 'advanced',
                'price' => 109.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Spring Framework'),
                'title' => 'Spring Framework 6 & Spring Boot 3',
                'level' => 'advanced',
                'price' => 119.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Frontend Courses
            [
                'course_track_id' => $this->getTrackId('HTML & CSS'),
                'title' => 'HTML5 & CSS3: Complete Web Development',
                'level' => 'beginner',
                'price' => 49.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('React.js'),
                'title' => 'React.js: The Complete Guide',
                'level' => 'intermediate',
                'price' => 94.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Vue.js'),
                'title' => 'Vue.js: The Complete Masterclass',
                'level' => 'intermediate',
                'price' => 89.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Angular'),
                'title' => 'Angular 17: The Complete Guide',
                'level' => 'advanced',
                'price' => 109.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Backend Courses
            [
                'course_track_id' => $this->getTrackId('Node.js'),
                'title' => 'Node.js & Express.js Masterclass',
                'level' => 'intermediate',
                'price' => 84.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Laravel'),
                'title' => 'Laravel 11: The Complete Guide',
                'level' => 'intermediate',
                'price' => 94.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],
            [
                'course_track_id' => $this->getTrackId('Django'),
                'title' => 'Django 5: The Complete Guide',
                'level' => 'intermediate',
                'price' => 89.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Full Stack Courses
            [
                'course_track_id' => $this->getTrackId('MERN Stack'),
                'title' => 'MERN Stack: MongoDB, Express, React, Node',
                'level' => 'advanced',
                'price' => 129.99,
                'language' => 'en',
                'certificate_available' => true,
                'status' => 'active'
            ],

            // Web Development - Frontend track
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
                'title' => 'Vue.js',
                'level' => 'intermediate',
                'price' => 59.99,
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

            // Web Development - Backend track
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
        return CourseTrack::where('name', $trackName)->first()->id;
    }
}