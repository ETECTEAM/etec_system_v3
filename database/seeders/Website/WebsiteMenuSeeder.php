<?php

namespace Database\Seeders\Website;

use App\Models\Menu;
use App\Models\Page;
use App\Models\PageHero;
use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class WebsiteMenuSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->firstOrCreate([], [
            'school_name' => 'Engineer of Technology and Electronic Center',
            'school_logo' => null,
        ]);

        $items = [
            ['name' => 'Home', 'title' => 'Home', 'slug' => 'home'],
            ['name' => 'About', 'title' => 'About Our School', 'slug' => 'about'],
            ['name' => 'Course', 'title' => 'Available Courses', 'slug' => 'course'],
            ['name' => 'News', 'title' => 'Latest School News', 'slug' => 'news'],
            ['name' => 'Events', 'title' => 'School Events', 'slug' => 'events'],
            ['name' => 'Video', 'title' => 'School Videos', 'slug' => 'video'],
            ['name' => 'Contact', 'title' => 'Contact Our School', 'slug' => 'contact'],
        ];

        foreach ($items as $index => $item) {
            $page = Page::query()->firstOrCreate(
                ['slug' => $item['slug']],
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
                ['page_id' => $page->id],
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
                ['name' => $item['name']],
                [
                    'page_id' => $page->id,
                    'position' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
