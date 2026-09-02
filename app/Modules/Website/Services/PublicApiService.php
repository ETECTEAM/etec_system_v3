<?php

namespace App\Modules\Website\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

class PublicApiService
{
    public function publicSettings(): array
    {
        $settings = DB::table('school_settings')->orderBy('id')->first();

        return [
            'school_name' => $settings->school_name ?? 'Engineer of Technology and Electronic Center',
            'school_logo' => null,
            'logo_url' => $this->publicImageDataUri($settings->school_logo ?? null),
        ];
    }

    public function publicMenus(): array
    {
        $menus = DB::table('menus')
            ->join('pages', 'pages.id', '=', 'menus.page_id')
            ->where('menus.is_active', true)
            ->where('pages.is_active', true)
            ->orderBy('menus.position')
            ->orderBy('menus.id')
            ->select([
                'menus.id',
                'menus.name',
                'menus.parent_id',
                'menus.position',
                'pages.slug',
            ])
            ->get();

        $children = $menus
            ->whereNotNull('parent_id')
            ->groupBy('parent_id');

        return $menus
            ->whereNull('parent_id')
            ->map(fn (stdClass $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'position' => $menu->position,
                'url' => $this->pageUrl($menu->slug),
                'slug' => $menu->slug,
                'children' => ($children[$menu->id] ?? collect())
                    ->map(fn (stdClass $child): array => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'position' => $child->position,
                        'url' => $this->pageUrl($child->slug),
                        'slug' => $child->slug,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function home(): array
    {
        return [
            'page' => $this->publicPageBySlug('home', false),
            'courses' => $this->publicCourses(6),
            'news' => $this->publicFeaturedNews(6),
            'events' => [],
            'videos' => $this->publicFeaturedVideos(6),
            'testimonials' => [],
        ];
    }

    public function featured(): array
    {
        return [
            'courses' => $this->publicCourses(6),
            'news' => $this->publicFeaturedNews(6),
            'videos' => $this->publicFeaturedVideos(6),
        ];
    }

    public function publicPageBySlug(string $slug, bool $fail = true): ?array
    {
        $page = DB::table('pages')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            abort_if($fail, 404);

            return null;
        }

        return $this->presentPage($page);
    }

    public function publicCourses(?int $limit = null): array
    {
        $query = $this->coursesQuery()
            ->where('courses.status', 'active')
            ->orderByDesc('courses.id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (stdClass $course): array => $this->presentCourse($course))
            ->values()
            ->all();
    }

    public function paginatedPublicCourses(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);
        $sortBy = in_array($filters['sort_by'] ?? null, ['title', 'level', 'price', 'created_at'], true)
            ? 'courses.'.$filters['sort_by']
            : 'courses.id';
        $sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = $this->coursesQuery()->where('courses.status', 'active');

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($query) use ($search): void {
                $query->where('courses.title', 'like', "%{$search}%")
                    ->orWhere('courses.slug', 'like', "%{$search}%")
                    ->orWhere('course_tracks.name', 'like', "%{$search}%")
                    ->orWhere('sub_categories.name', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%");
            });
        }

        if ($level = trim((string) ($filters['level'] ?? ''))) {
            $query->where('courses.level', $level);
        }

        if ($category = trim((string) ($filters['category'] ?? ''))) {
            $query->where('categories.name', $category);
        }

        if ($subCategory = trim((string) ($filters['sub_category'] ?? ''))) {
            $query->where(function ($query) use ($subCategory): void {
                $query->where('sub_categories.slug', $subCategory)
                    ->orWhere('sub_categories.name', $subCategory);
            });
        }

        $paginator = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (stdClass $course): array => $this->presentCourse($course))
                ->values()
                ->all(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function publicCourseDetail(string $slug): array
    {
        $course = $this->coursesQuery()
            ->where('courses.slug', $slug)
            ->where('courses.status', 'active')
            ->first();

        abort_unless($course, 404);

        return $this->presentCourse($course, true);
    }

    public function publicFeaturedNews(?int $limit = null): array
    {
        $query = $this->newsQuery()
            ->where('news.is_active', true)
            ->where('news.is_featured', true)
            ->orderBy('news.sort_order')
            ->orderByDesc('news.published_at')
            ->orderByDesc('news.id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (stdClass $news): array => $this->presentNews($news))
            ->values()
            ->all();
    }

    public function paginatedPublicNews(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);
        $query = $this->newsQuery()->where('news.is_active', true);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($query) use ($search): void {
                $query->where('news.title', 'like', "%{$search}%")
                    ->orWhere('news.slug', 'like', "%{$search}%")
                    ->orWhere('news.excerpt', 'like', "%{$search}%")
                    ->orWhere('news.description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('news.is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query
            ->orderBy('news.sort_order')
            ->orderByDesc('news.published_at')
            ->orderByDesc('news.id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (stdClass $news): array => $this->presentNews($news))
                ->values()
                ->all(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function publicNewsDetail(string $slug): array
    {
        $news = $this->newsQuery()
            ->where('news.slug', $slug)
            ->where('news.is_active', true)
            ->first();

        abort_unless($news, 404);

        return $this->presentNews($news);
    }

    public function publicFeaturedVideos(?int $limit = null): array
    {
        $query = $this->videosQuery()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (stdClass $video): array => $this->presentVideo($video))
            ->values()
            ->all();
    }

    public function paginatedPublicVideos(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);
        $query = $this->videosQuery()->where('is_active', true);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (stdClass $video): array => $this->presentVideo($video))
                ->values()
                ->all(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    public function publicVideoDetail(string $slug): array
    {
        $video = $this->videosQuery()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        abort_unless($video, 404);

        return $this->presentVideo($video);
    }

    private function coursesQuery()
    {
        return DB::table('courses')
            ->leftJoin('course_tracks', 'course_tracks.id', '=', 'courses.course_track_id')
            ->leftJoin('sub_categories', 'sub_categories.id', '=', 'course_tracks.sub_category_id')
            ->leftJoin('categories', 'categories.id', '=', 'sub_categories.category_id')
            ->select([
                'courses.id',
                'courses.title',
                'courses.slug',
                'courses.level',
                'courses.price',
                'courses.thumbnail',
                'courses.created_at',
                'course_tracks.name as track_name',
                'sub_categories.name as sub_category_name',
                'categories.name as category_name',
            ]);
    }

    private function newsQuery()
    {
        return DB::table('news')
            ->leftJoin('users', 'users.id', '=', 'news.user_id')
            ->select([
                'news.id',
                'news.title',
                'news.slug',
                'news.excerpt',
                'news.description',
                'news.published_at',
                'news.sort_order',
                'news.is_featured',
                'news.is_active',
                'news.created_at',
                'users.name as author_name',
                'users.email as author_email',
            ]);
    }

    private function videosQuery()
    {
        return DB::table('website_videos')
            ->select([
                'id',
                'title',
                'slug',
                'description',
                'video_path',
                'thumbnail_path',
                'duration',
                'views_count',
                'sort_order',
                'is_featured',
                'created_at',
                'updated_at',
            ]);
    }

    private function presentCourse(stdClass $course, bool $includeLessons = false): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'level' => $course->level,
            'thumbnail' => null,
            'thumbnail_url' => $this->publicImageDataUri($course->thumbnail),
            'track' => $course->track_name,
            'sub_category' => $course->sub_category_name,
            'category' => $course->category_name,
            'lessons' => $includeLessons ? $this->courseLessons($course->id) : [],
        ];
    }

    private function courseLessons(int $courseId): array
    {
        return DB::table('course_lessons')
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->orderBy('order_number')
            ->orderBy('id')
            ->select([
                'id',
                'title',
                'slug',
                'description',
                'content',
                'video_url',
                'duration',
                'order_number',
            ])
            ->get()
            ->map(fn (stdClass $lesson): array => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'description' => $lesson->description,
                'content' => $lesson->content,
                'video_url' => $lesson->video_url,
                'duration' => $lesson->duration,
                'order_number' => $lesson->order_number,
            ])
            ->values()
            ->all();
    }

    private function presentNews(stdClass $news): array
    {
        return [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'author' => $news->author_name ?? $news->author_email,
            'excerpt' => $news->excerpt,
            'description' => $this->sanitizeContent($news->description),
            'published_at' => $this->date($news->published_at),
            'sort_order' => $news->sort_order,
            'is_featured' => (bool) $news->is_featured,
            'is_active' => (bool) $news->is_active,
            'created_at' => $this->date($news->created_at),
            'images' => $this->newsImages($news->id),
        ];
    }

    private function newsImages(int $newsId): array
    {
        return DB::table('news_images')
            ->where('news_id', $newsId)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->select(['id', 'image', 'position', 'is_active'])
            ->get()
            ->map(fn (stdClass $image): array => [
                'id' => $image->id,
                'image' => null,
                'image_url' => $this->publicImageDataUri($image->image),
                'position' => $image->position,
                'is_active' => (bool) $image->is_active,
            ])
            ->values()
            ->all();
    }

    private function presentVideo(stdClass $video): array
    {
        return [
            'id' => $video->id,
            'slug' => $video->slug,
            'title' => $video->title,
            'description' => $video->description,
            'video_url' => $this->storageUrl($video->video_path, $video->updated_at),
            'thumbnail_url' => $this->publicImageDataUri($video->thumbnail_path),
            'duration' => $video->duration,
            'views_count' => $video->views_count,
            'sort_order' => $video->sort_order,
            'is_featured' => (bool) $video->is_featured,
            'created_at' => $this->date($video->created_at),
        ];
    }

    private function presentPage(stdClass $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $this->sanitizeContent($page->content),
            'is_active' => (bool) $page->is_active,
            'created_at' => $this->date($page->created_at),
            'menus' => $this->pageMenus($page->id),
            'hero' => $this->pageHero($page->id),
        ];
    }

    private function pageMenus(int $pageId): array
    {
        return DB::table('menus')
            ->where('page_id', $pageId)
            ->orderBy('position')
            ->orderBy('id')
            ->select(['id', 'name', 'position', 'is_active'])
            ->get()
            ->map(fn (stdClass $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'position' => $menu->position,
                'is_active' => (bool) $menu->is_active,
            ])
            ->values()
            ->all();
    }

    private function pageHero(int $pageId): ?array
    {
        $hero = DB::table('page_heroes')
            ->where('page_id', $pageId)
            ->first();

        if (! $hero) {
            return null;
        }

        return [
            'id' => $hero->id,
            'title' => $hero->title,
            'subtitle' => $hero->subtitle,
            'description' => $hero->description,
            'background_image' => null,
            'background_image_url' => $this->publicImageDataUri($hero->background_image),
            'primary_button_text' => $hero->primary_button_text,
            'primary_button_url' => $hero->primary_button_url,
            'secondary_button_text' => $hero->secondary_button_text,
            'secondary_button_url' => $hero->secondary_button_url,
            'overlay_opacity' => $hero->overlay_opacity,
            'text_alignment' => $hero->text_alignment,
            'is_active' => (bool) $hero->is_active,
            'images' => $this->pageHeroImages($hero->id),
        ];
    }

    private function pageHeroImages(int $heroId): array
    {
        return DB::table('page_hero_images')
            ->where('page_hero_id', $heroId)
            ->orderBy('position')
            ->orderBy('id')
            ->select(['id', 'image', 'position', 'is_active'])
            ->get()
            ->map(fn (stdClass $image): array => [
                'id' => $image->id,
                'image' => null,
                'image_url' => $this->publicImageDataUri($image->image),
                'position' => $image->position,
                'is_active' => (bool) $image->is_active,
            ])
            ->values()
            ->all();
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    private function pageUrl(?string $slug): ?string
    {
        if (! $slug) {
            return null;
        }

        return $slug === 'home' ? '/home' : '/'.$slug;
    }

    private function storageUrl(?string $path, mixed $updatedAt): ?string
    {
        if (! $path) {
            return null;
        }

        $version = $updatedAt ? Carbon::parse($updatedAt)->timestamp : time();

        return '/storage/'.ltrim($path, '/').'?v='.$version;
    }

    private function date(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    // FILE: disabled - not using file uploads
    // private function publicImageDataUri(?string $path): ?string
    // {
    //     if (! $path || Str::startsWith($path, ['data:', 'http://', 'https://', '//'])) {
    //         return $path;
    //     }
    //
    //     $path = ltrim($path, '/');
    //
    //     if (! Storage::disk('public')->exists($path)) {
    //         return null;
    //     }
    //
    //     $mimeType = Storage::disk('public')->mimeType($path);
    //
    //     if (! is_string($mimeType) || ! Str::startsWith($mimeType, 'image/')) {
    //         return null;
    //     }
    //
    //     return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('public')->get($path));
    // }

    // Stub: returns null when file uploads are disabled
    private function publicImageDataUri(?string $path): ?string
    {
        return null;
    }

    private function sanitizeContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $content = preg_replace('#<(script|style|iframe|object|embed|form|input|button)\b[^>]*>.*?</\1>#is', '', $content) ?? '';
        $content = preg_replace('#</?(script|style|iframe|object|embed|form|input|button)\b[^>]*>#is', '', $content) ?? '';
        $content = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? '';
        $content = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '$1="#"', $content) ?? '';

        return trim($content);
    }
}
