<?php

namespace Database\Seeders\Website;

use App\Models\Menu;
use App\Models\News;
use App\Models\Page;
use App\Models\PageHero;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class WebsiteMenuSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->firstOrCreate([], [
            'school_name' => 'Engineer of Technology and Electronic Center',
            'school_logo' => null,
        ]);

        $this->seedPagesAndMenus();
        $this->seedNews();
    }

    private function seedPagesAndMenus(): void
    {
        $items = [
            [
                'name' => 'Home',
                'title' => 'Home',
                'slug' => 'home',
            ],
            [
                'name' => 'About',
                'title' => 'About Our School',
                'slug' => 'about',
            ],
            [
                'name' => 'Course',
                'title' => 'Available Courses',
                'slug' => 'course',
            ],
            [
                'name' => 'News',
                'title' => 'Latest School News',
                'slug' => 'news',
            ],
            [
                'name' => 'Events',
                'title' => 'School Events',
                'slug' => 'events',
            ],
            [
                'name' => 'Video',
                'title' => 'School Videos',
                'slug' => 'video',
            ],
            [
                'name' => 'Contact',
                'title' => 'Contact Our School',
                'slug' => 'contact',
            ],
        ];

        foreach ($items as $index => $item) {
            $page = Page::query()->firstOrCreate(
                [
                    'slug' => $item['slug'],
                ],
                [
                    'title' => $item['title'],
                    'content' => null,
                    'is_active' => true,
                ],
            );

            $page->update([
                'title' => $page->title ?: $item['title'],
                'is_active' => true,
            ]);

            PageHero::query()->firstOrCreate(
                [
                    'page_id' => $page->id,
                ],
                [
                    'title' => $item['title'],
                    'subtitle' => 'Engineer of Technology and Electronic Center',
                    'description' => 'Update this hero section from Website Management in the dashboard.',
                    'overlay_opacity' => 55,
                    'text_alignment' => 'center',
                    'is_active' => true,
                ],
            );

            Menu::query()->updateOrCreate(
                [
                    'name' => $item['name'],
                ],
                [
                    'page_id' => $page->id,
                    'position' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedNews(): void
    {
        $userId = User::query()->value('id');

        if (! $userId) {
            throw new RuntimeException(
                'No user was found. Please run your user or admin seeder before WebsiteMenuSeeder.'
            );
        }

        $newsItems = [
            [
                'title' => 'Welcome to Our New Academic Year',
                'slug' => 'welcome-to-our-new-academic-year',
                'excerpt' => 'Our school warmly welcomes students and teachers to a new year of learning, creativity, and achievement.',
                'description' => '
                    <p>Engineer of Technology and Electronic Center is pleased to welcome all students, teachers, and parents to the new academic year.</p>

                    <p>This year, we remain committed to providing practical education, modern technology training, and a supportive learning environment for every student.</p>

                    <p>We look forward to another successful year filled with knowledge, innovation, teamwork, and personal growth.</p>
                ',
                'published_at' => now()->subDays(2)->toDateString(),
                'sort_order' => 1,
                'is_featured' => true,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/new-academic-year.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'title' => 'Students Complete Practical Technology Training',
                'slug' => 'students-complete-practical-technology-training',
                'excerpt' => 'Students successfully completed hands-on technology training designed to strengthen their practical skills.',
                'description' => '
                    <p>Our students recently completed a practical technology training program focused on real-world skills and problem-solving.</p>

                    <p>During the training, students worked with modern tools, completed practical exercises, and received guidance from experienced instructors.</p>

                    <p>The program helped students improve their technical confidence and prepare for future academic and career opportunities.</p>
                ',
                'published_at' => now()->subDays(5)->toDateString(),
                'sort_order' => 2,
                'is_featured' => true,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/practical-technology-training.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'image' => 'uploads/news/student-training-activity.jpg',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'title' => 'New Computer Laboratory Opens for Students',
                'slug' => 'new-computer-laboratory-opens-for-students',
                'excerpt' => 'A new computer laboratory is now available to support digital learning and practical IT education.',
                'description' => '
                    <p>Our new computer laboratory is officially open and ready to support students in their digital learning journey.</p>

                    <p>The laboratory provides students with access to computers, learning software, internet resources, and a comfortable environment for practical study.</p>

                    <p>Students will use the laboratory for programming, design, research, and other technology-related subjects.</p>
                ',
                'published_at' => now()->subDays(8)->toDateString(),
                'sort_order' => 3,
                'is_featured' => false,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/new-computer-laboratory.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'title' => 'School Hosts Career Guidance Workshop',
                'slug' => 'school-hosts-career-guidance-workshop',
                'excerpt' => 'Students joined a career guidance workshop to explore education pathways and future job opportunities.',
                'description' => '
                    <p>Engineer of Technology and Electronic Center organized a career guidance workshop for students interested in technology and engineering careers.</p>

                    <p>The workshop introduced students to different career pathways, workplace expectations, and the skills required by modern employers.</p>

                    <p>Students also had the opportunity to ask questions and receive advice from instructors and industry professionals.</p>
                ',
                'published_at' => now()->subDays(12)->toDateString(),
                'sort_order' => 4,
                'is_featured' => false,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/career-guidance-workshop.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'title' => 'Student Project Exhibition Showcases Innovation',
                'slug' => 'student-project-exhibition-showcases-innovation',
                'excerpt' => 'Students presented creative technology and engineering projects during the school project exhibition.',
                'description' => '
                    <p>Students proudly presented their work during our latest student project exhibition.</p>

                    <p>The exhibition included technology projects, electronic systems, creative designs, and practical solutions developed during class.</p>

                    <p>The event allowed students to demonstrate their knowledge, communication skills, creativity, and teamwork.</p>
                ',
                'published_at' => now()->subDays(15)->toDateString(),
                'sort_order' => 5,
                'is_featured' => true,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/student-project-exhibition.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'image' => 'uploads/news/student-project-presentation.jpg',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'title' => 'Registration Is Open for New Technology Courses',
                'slug' => 'registration-open-for-new-technology-courses',
                'excerpt' => 'Students can now register for our latest technology, electronics, and computer training courses.',
                'description' => '
                    <p>Registration is now open for our upcoming technology and electronics training courses.</p>

                    <p>The courses are designed for students who want to develop practical skills in computers, programming, electronics, and modern technology.</p>

                    <p>Interested students and parents may contact the school for course schedules, fees, and registration information.</p>
                ',
                'published_at' => now()->subDays(20)->toDateString(),
                'sort_order' => 6,
                'is_featured' => false,
                'is_active' => true,
                'images' => [
                    [
                        'image' => 'uploads/news/course-registration.jpg',
                        'position' => 1,
                        'is_active' => true,
                    ],
                ],
            ],
        ];

        foreach ($newsItems as $item) {
            $images = $item['images'];

            unset($item['images']);

            $news = News::query()->updateOrCreate(
                [
                    'slug' => $item['slug'],
                ],
                [
                    'title' => $item['title'],
                    'user_id' => $userId,
                    'excerpt' => $item['excerpt'],
                    'description' => trim($item['description']),
                    'published_at' => $item['published_at'],
                    'sort_order' => $item['sort_order'],
                    'is_featured' => $item['is_featured'],
                    'is_active' => $item['is_active'],
                ],
            );

            foreach ($images as $image) {
                $news->images()->updateOrCreate(
                    [
                        'image' => $image['image'],
                    ],
                    [
                        'position' => $image['position'],
                        'is_active' => $image['is_active'],
                    ],
                );
            }
        }
    }
}