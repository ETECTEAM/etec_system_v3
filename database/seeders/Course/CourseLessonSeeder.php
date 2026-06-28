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
            // Python Course Lessons
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Introduction to Python',
                'order_number' => 1,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Variables and Data Types',
                'order_number' => 2,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Control Flow: If Statements and Loops',
                'order_number' => 3,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Functions and Scope',
                'order_number' => 4,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Lists and Tuples',
                'order_number' => 5,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Dictionaries and Sets',
                'order_number' => 6,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Object-Oriented Programming in Python',
                'order_number' => 7,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python File Handling',
                'order_number' => 8,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Python Modules and Packages',
                'order_number' => 9,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Python Programming: From Zero to Hero'),
                'title' => 'Build Your First Python Project',
                'order_number' => 10,
                'status' => 'active'
            ],

            // Java Course Lessons
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Introduction to Java',
                'order_number' => 1,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Java Syntax and Variables',
                'order_number' => 2,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Java Operators and Expressions',
                'order_number' => 3,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Control Flow in Java',
                'order_number' => 4,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Arrays in Java',
                'order_number' => 5,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Object-Oriented Programming in Java',
                'order_number' => 6,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Java Collections Framework',
                'order_number' => 7,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Exception Handling in Java',
                'order_number' => 8,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Multithreading in Java',
                'order_number' => 9,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Java Masterclass: Complete Java Programming'),
                'title' => 'Java Project: Build a Complete Application',
                'order_number' => 10,
                'status' => 'active'
            ],

            // React Course Lessons
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'Introduction to React.js',
                'order_number' => 1,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React Components and Props',
                'order_number' => 2,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React State Management with Hooks',
                'order_number' => 3,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React Router: Navigation in React',
                'order_number' => 4,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'State Management with Redux',
                'order_number' => 5,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React Forms and Validation',
                'order_number' => 6,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'API Integration in React',
                'order_number' => 7,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React Performance Optimization',
                'order_number' => 8,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'Testing React Applications',
                'order_number' => 9,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('React.js: The Complete Guide'),
                'title' => 'React Project: Build a Full Application',
                'order_number' => 10,
                'status' => 'active'
            ],

            // Spring Framework Course Lessons
            [
                'course_id' => $this->getCourseId('Spring Framework 6 & Spring Boot 3'),
                'title' => 'Introduction to Spring Framework',
                'order_number' => 1,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Spring Framework 6 & Spring Boot 3'),
                'title' => 'Spring Boot 3 Basics',
                'order_number' => 2,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Spring Framework 6 & Spring Boot 3'),
                'title' => 'Spring Data JPA and Hibernate',
                'order_number' => 3,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Spring Framework 6 & Spring Boot 3'),
                'title' => 'Spring Security and Authentication',
                'order_number' => 4,
                'status' => 'active'
            ],
            [
                'course_id' => $this->getCourseId('Spring Framework 6 & Spring Boot 3'),
                'title' => 'Spring Boot Microservices',
                'order_number' => 5,
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
        return Course::where('title', $courseTitle)->first()->id;
    }
}