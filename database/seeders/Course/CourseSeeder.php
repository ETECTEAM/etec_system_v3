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
                'title' => 'Basic / Advance C++ / OOP /MySQL',
                'level' => 'beginner',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction'],
                    ['title' => 'Variables'],
                    ['title' => 'Data Types'],
                    ['title' => 'Operators'],
                    ['title' => 'Condition'],
                    ['title' => 'Loops'],
                    ['title' => 'Functions'],
                    ['title' => 'Arrays'],
                    ['title' => 'Pointers'],
                    ['title' => 'Memory Allocation'],
                    ['title' => 'Structures'],
                    ['title' => 'OOP Basics'],
                    ['title' => 'Classes and Objects'],
                    ['title' => 'Access Specifiers'],
                    ['title' => 'Constructors'],
                    ['title' => 'Encapsulation'],
                    ['title' => 'Inheritance'],
                    ['title' => 'Polymorphism'],
                    ['title' => 'Abstraction'],
                    ['title' => 'File I/O'],
                    ['title' => 'Database'],
                ],
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
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Intro JavaScript'], ['title' => 'Variables'], ['title' => 'Data Types'],
                    ['title' => 'Operators'], ['title' => 'Condition'], ['title' => 'Loop'],
                    ['title' => 'Function'], ['title' => 'Array'], ['title' => 'Array Method'],
                    ['title' => 'Object'], ['title' => 'OOP'], ['title' => 'DOM'], ['title' => 'ES6'],
                    ['title' => 'Destructuring'], ['title' => 'Spread & Rest Operator'],
                    ['title' => 'Template Literals'], ['title' => 'Modules'], ['title' => 'JSON'],
                    ['title' => 'Async JavaScript'], ['title' => 'Promise'], ['title' => 'Async / Await'],
                    ['title' => 'Fetch API'], ['title' => 'Error Handling'], ['title' => 'Local Storage'],
                    ['title' => 'Intro React.js'], ['title' => 'Installation'], ['title' => 'Project Structure'],
                    ['title' => 'JSX Syntax'], ['title' => 'Components'], ['title' => 'Props'],
                    ['title' => 'State'], ['title' => 'Event Handling'], ['title' => 'Conditional Rendering'],
                    ['title' => 'List Rendering'], ['title' => 'Forms'], ['title' => 'React Hook'],
                    ['title' => 'useState'], ['title' => 'useEffect'], ['title' => 'useRef'],
                    ['title' => 'Custom Hook'], ['title' => 'React Router'], ['title' => 'API Integration'],
                    ['title' => 'React with Bootstrap'], ['title' => 'React with Tailwind CSS'],
                    ['title' => 'Authentication'], ['title' => 'State Management'], ['title' => 'Final Project'],
                    ['title' => 'GitHub'],
                ],
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'Web Frontend + Reactjs, Domain Hosting',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction HTML'], ['title' => 'Basic Tag'], ['title' => 'Text Formatting'],
                    ['title' => 'Attributes'], ['title' => 'Link and Image'], ['title' => 'Lists'],
                    ['title' => 'Table'], ['title' => 'Forms'], ['title' => 'CSS Basics'],
                    ['title' => 'Selectors'], ['title' => 'Properties'], ['title' => 'CSS Styling & Effects'],
                    ['title' => 'Box Model'], ['title' => 'Layout'], ['title' => 'Animation (Optional)'],
                    ['title' => 'Bootstrap'], ['title' => 'Modal'], ['title' => 'Offcanvas'],
                    ['title' => 'Form'], ['title' => 'Table'], ['title' => 'Intro JS'],
                    ['title' => 'Variables'], ['title' => 'Data types'], ['title' => 'Operators'],
                    ['title' => 'Condition'], ['title' => 'Loop'], ['title' => 'Function'],
                    ['title' => 'Array'], ['title' => 'Array Method'], ['title' => 'Object'],
                    ['title' => 'OOP'], ['title' => 'DOM'], ['title' => 'ES6'],
                    ['title' => 'Intro React.js'], ['title' => 'Installation'], ['title' => 'JSX Syntax'],
                    ['title' => 'Component'], ['title' => 'State & Props'], ['title' => 'React Hook'],
                    ['title' => 'React with Bootstrap'], ['title' => 'React with Tailwind CSS'],
                    ['title' => 'React Router'], ['title' => 'API & Fetch'], ['title' => 'Form Handling'],
                    ['title' => 'State Management'], ['title' => 'Authentication'], ['title' => 'Final Project'],
                    ['title' => 'GitHub'],
                ],
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'Web Frontend + jQuery, Domain Hosting',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction HTML'], ['title' => 'Basic Tag'], ['title' => 'Text Formatting'],
                    ['title' => 'Attributes'], ['title' => 'Link and Image'], ['title' => 'Lists'],
                    ['title' => 'Table'], ['title' => 'Forms'], ['title' => 'CSS Basics'],
                    ['title' => 'Selectors'], ['title' => 'Properties'], ['title' => 'CSS Styling & Effects'],
                    ['title' => 'Box Model'], ['title' => 'Layout'], ['title' => 'Animation (Optional)'],
                    ['title' => 'Bootstrap'], ['title' => 'Modal'], ['title' => 'Offcanvas'],
                    ['title' => 'Form'], ['title' => 'Table'], ['title' => 'Intro JS'],
                    ['title' => 'Variables'], ['title' => 'Data Types'], ['title' => 'Operators'],
                    ['title' => 'Condition'], ['title' => 'Loop'], ['title' => 'Function'],
                    ['title' => 'Array'], ['title' => 'Array Method'], ['title' => 'Object'],
                    ['title' => 'DOM'], ['title' => 'ES6'], ['title' => 'Intro jQuery'],
                    ['title' => 'jQuery Installation'], ['title' => 'jQuery Selectors'], ['title' => 'jQuery Events'],
                    ['title' => 'jQuery Effects'], ['title' => 'jQuery DOM Manipulation'], ['title' => 'jQuery Attributes'],
                    ['title' => 'jQuery CSS'], ['title' => 'jQuery Traversing'], ['title' => 'jQuery AJAX'],
                    ['title' => 'jQuery Form Handling'], ['title' => 'jQuery Validation'], ['title' => 'jQuery with Bootstrap'],
                    ['title' => 'JSON & API'], ['title' => 'Final Project'], ['title' => 'GitHub'],
                ],
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'Web Frontend + Vue.js, Domain Hosting',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction HTML'], ['title' => 'Basic Tag'], ['title' => 'Text Formatting'],
                    ['title' => 'Attributes'], ['title' => 'Link and Image'], ['title' => 'Lists'],
                    ['title' => 'Table'], ['title' => 'Forms'], ['title' => 'CSS Basics'],
                    ['title' => 'Selectors'], ['title' => 'Properties'], ['title' => 'CSS Styling & Effects'],
                    ['title' => 'Box Model'], ['title' => 'Layout'], ['title' => 'Animation (Optional)'],
                    ['title' => 'Bootstrap'], ['title' => 'Modal'], ['title' => 'Offcanvas'],
                    ['title' => 'Form'], ['title' => 'Table'], ['title' => 'Intro JS'],
                    ['title' => 'Variables'], ['title' => 'Data Types'], ['title' => 'Operators'],
                    ['title' => 'Condition'], ['title' => 'Loop'], ['title' => 'Function'],
                    ['title' => 'Array'], ['title' => 'Array Method'], ['title' => 'Object'],
                    ['title' => 'OOP'], ['title' => 'DOM'], ['title' => 'ES6'],
                    ['title' => 'Intro Vue.js'], ['title' => 'Installation'], ['title' => 'Vue Project Structure'],
                    ['title' => 'Template Syntax'], ['title' => 'Directives'], ['title' => 'Data Binding'],
                    ['title' => 'Methods'], ['title' => 'Computed Properties'], ['title' => 'Watchers'],
                    ['title' => 'Components'], ['title' => 'Props'], ['title' => 'Events'],
                    ['title' => 'Slots'], ['title' => 'Vue Router'], ['title' => 'Vue Forms'],
                    ['title' => 'Vue Validation'], ['title' => 'API & Fetch'], ['title' => 'State Management'],
                    ['title' => 'Authentication'], ['title' => 'Vue with Bootstrap'], ['title' => 'Vue with Tailwind CSS'],
                    ['title' => 'Final Project'], ['title' => 'GitHub'],
                ],
            ],
            [
                'track' => 'Frontend Course',
                'title' => 'JavaScript + Vue.js, Domain Hosting',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Intro JavaScript'], ['title' => 'Variables'], ['title' => 'Data Types'],
                    ['title' => 'Operators'], ['title' => 'Condition'], ['title' => 'Loop'],
                    ['title' => 'Function'], ['title' => 'Array'], ['title' => 'Array Method'],
                    ['title' => 'Object'], ['title' => 'OOP'], ['title' => 'DOM'], ['title' => 'ES6'],
                    ['title' => 'Destructuring'], ['title' => 'Spread & Rest Operator'],
                    ['title' => 'Template Literals'], ['title' => 'Modules'], ['title' => 'JSON'],
                    ['title' => 'Async JavaScript'], ['title' => 'Promise'], ['title' => 'Async / Await'],
                    ['title' => 'Fetch API'], ['title' => 'Error Handling'], ['title' => 'Local Storage'],
                    ['title' => 'Intro Vue.js'], ['title' => 'Installation'], ['title' => 'Vue Project Structure'],
                    ['title' => 'Template Syntax'], ['title' => 'Data Binding'], ['title' => 'Directives'],
                    ['title' => 'Methods'], ['title' => 'Computed Properties'], ['title' => 'Watchers'],
                    ['title' => 'Event Handling'], ['title' => 'Conditional Rendering'], ['title' => 'List Rendering'],
                    ['title' => 'Components'], ['title' => 'Props'], ['title' => 'Events'], ['title' => 'Slots'],
                    ['title' => 'Vue Router'], ['title' => 'Forms'], ['title' => 'Form Validation'],
                    ['title' => 'API Integration'], ['title' => 'Vue with Bootstrap'],
                    ['title' => 'Vue with Tailwind CSS'], ['title' => 'State Management'],
                    ['title' => 'Authentication'], ['title' => 'Final Project'], ['title' => 'GitHub'],
                ],
            ],

            // Web Full-Stack Course (Backend Track)
            [
                'track' => 'Backend Course',
                'title' => 'PHP / MySQL + Ajax + Projects Web Backend (Basic/Advance)',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction'],
                    ['title' => 'Variable'],
                    ['title' => 'Condition'],
                    ['title' => 'Loop'],
                    ['title' => 'Function'],
                    ['title' => 'Array'],
                    ['title' => 'OOP'],
                    ['title' => 'Move Upload File'],
                    ['title' => 'Database'],
                    ['title' => 'CRUD'],
                    ['title' => 'CRUD + Image'],
                    ['title' => 'Review jquery'],
                    ['title' => 'CRUD + Ajax'],
                    ['title' => 'MVC Concept'],
                    ['title' => 'Project'],
                ],
            ],
            [
                'track' => 'Backend Course',
                'title' => 'PHP / MySQL + Laravel + Projects Web Backend (Basic/Advance)',
                'level' => 'advanced',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Introduction to PHP'],
                    ['title' => 'Variable'],
                    ['title' => 'Condition'],
                    ['title' => 'Loop'],
                    ['title' => 'Function'],
                    ['title' => 'Array'],
                    ['title' => 'OOP'],
                    ['title' => 'Move Upload file'],
                    ['title' => 'Database'],
                    ['title' => 'CRUD'],
                    ['title' => 'CRUD-Image'],
                    ['title' => 'Middleware'],
                    ['title' => 'Introduction To Laravel'],
                    ['title' => 'MVC Concept'],
                    ['title' => 'Route'],
                    ['title' => 'Controller'],
                    ['title' => 'Database & Migration'],
                    ['title' => 'CRUD'],
                    ['title' => 'CRUD + Model'],
                    ['title' => 'CRUD-Image'],
                    ['title' => 'Middleware'],
                    ['title' => 'Laravel API'],
                    ['title' => 'Project Laravel'],
                ],
            ],
            [
                'track' => 'Backend Course',
                'title' => 'Node.js+Express',
                'level' => 'intermediate',
                'status' => 'active',
                'lessons' => [
                    ['title' => 'Review JavaScript'], ['title' => 'Variables'], ['title' => 'Data Types'],
                    ['title' => 'Operators'], ['title' => 'Condition'], ['title' => 'Loop'],
                    ['title' => 'Function'], ['title' => 'Array'], ['title' => 'Array Method'],
                    ['title' => 'Object'], ['title' => 'OOP'], ['title' => 'DOM'], ['title' => 'ES6'],
                    ['title' => 'Destructuring'], ['title' => 'Spread & Rest Operator'],
                    ['title' => 'Template Literals'], ['title' => 'Modules'], ['title' => 'JSON'],
                    ['title' => 'Async JavaScript'], ['title' => 'Promise'], ['title' => 'Async / Await'],
                    ['title' => 'Error Handling'], ['title' => 'Fetch API'],
                    ['title' => 'Intro Node.js'], ['title' => 'Node.js Installation'],
                    ['title' => 'Node.js Project Structure'], ['title' => 'NPM & package.json'],
                    ['title' => 'Modules'], ['title' => 'CommonJS & ES Modules'],
                    ['title' => 'Built-in Modules'], ['title' => 'File System'], ['title' => 'Path Module'],
                    ['title' => 'HTTP Module'], ['title' => 'Environment Variables'],
                    ['title' => 'NPM Packages'], ['title' => 'Nodemon'],
                    ['title' => 'Intro Express.js'], ['title' => 'Express Installation'],
                    ['title' => 'Express Project Structure'], ['title' => 'Server & Routing'],
                    ['title' => 'Request & Response'], ['title' => 'Route Parameters'],
                    ['title' => 'Query Parameters'], ['title' => 'Middleware'], ['title' => 'Static Files'],
                    ['title' => 'Express Router'], ['title' => 'Controller'], ['title' => 'Service Layer'],
                    ['title' => 'REST API'], ['title' => 'HTTP Methods'], ['title' => 'Status Codes'],
                    ['title' => 'JSON API'], ['title' => 'Postman'], ['title' => 'CRUD API'],
                    ['title' => 'Validation'], ['title' => 'Error Handling'],
                    ['title' => 'MongoDB'], ['title' => 'MongoDB Installation'], ['title' => 'Mongoose'],
                    ['title' => 'Schema & Model'], ['title' => 'Database CRUD'], ['title' => 'Relationships'],
                    ['title' => 'API & MongoDB'],
                    ['title' => 'Authentication'], ['title' => 'Password Hashing'],
                    ['title' => 'JWT Authentication'], ['title' => 'Authorization'], ['title' => 'CORS'],
                    ['title' => 'File Upload'], ['title' => 'Pagination'], ['title' => 'Search & Filtering'],
                    ['title' => 'API Security'], ['title' => 'API Testing'], ['title' => 'Deployment'],
                    ['title' => 'Environment Configuration'], ['title' => 'Final Project'], ['title' => 'GitHub'],
                ],
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

            // Optional subject breakdown. order_number keeps the list in the
            // order defined above, including intentionally repeated titles.
            $lessonSlugCounts = [];

            foreach ($course['lessons'] ?? [] as $index => $lesson) {
                $baseLessonSlug = Str::slug($lesson['title']);
                $lessonSlugCounts[$baseLessonSlug] = ($lessonSlugCounts[$baseLessonSlug] ?? 0) + 1;
                $lessonSlug = $lessonSlugCounts[$baseLessonSlug] === 1
                    ? $baseLessonSlug
                    : $baseLessonSlug . '-' . $lessonSlugCounts[$baseLessonSlug];

                CourseLesson::updateOrCreate(
                    ['course_id' => $createdCourse->id, 'slug' => $lessonSlug],
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
