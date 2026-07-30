<?php

namespace App\Modules\Website\Services;

use App\Models\Category;
use App\Models\Course;
use App\Models\Menu;
use App\Models\News;
use App\Models\Page;
use App\Models\SchoolSetting;
use App\Models\WebsiteVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebsiteContentService
{
    public function settings(): SchoolSetting
    {
        return SchoolSetting::query()->firstOrCreate([], [
            'school_name' => 'Engineer of Technology and Electronic Center',
            'school_logo' => null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicMenus(): array
    {
        return Menu::query()
            ->with([
                'page:id,title,slug,is_active',
                'children' => fn ($query) => $query
                    ->with('page:id,title,slug,is_active')
                    ->where('is_active', true)
                    ->whereHas('page', fn ($pageQuery) => $pageQuery->where('is_active', true)),
            ])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('page', fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'position' => $menu->position,
                'url' => $menu->resolved_url,
                'slug' => $menu->page?->slug,
                'children' => $menu->children->map(fn (Menu $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'position' => $child->position,
                    'url' => $child->resolved_url,
                    'slug' => $child->page?->slug,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicSettings(): array
    {
        $settings = $this->settings();

        return [
            'school_name' => $settings->school_name,
            'school_logo' => null,
            'logo_url' => $this->publicImageDataUri($settings->school_logo),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicCourses(?int $limit = null): array
    {
        $query = Course::query()
            ->with('track.subCategory.category')
            ->where('status', 'active')
            ->orderByDesc('id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Course $course): array => $this->presentCourse($course))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function paginatedPublicCourses(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);
        $sortBy = in_array($filters['sort_by'] ?? null, ['title', 'level', 'duration', 'price', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        /** @var LengthAwarePaginator $paginator */
        $query = Course::query()
            ->with('track.subCategory.category')
            ->where('status', 'active');

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('track', fn ($trackQuery) => $trackQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('track.subCategory', fn ($subCategoryQuery) => $subCategoryQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('track.subCategory.category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($level = trim((string) ($filters['level'] ?? ''))) {
            $query->where('level', $level);
        }

        if ($duration = trim((string) ($filters['duration'] ?? ''))) {
            $query->where('duration', '<=', (int) $duration);
        }

        if ($category = trim((string) ($filters['category'] ?? ''))) {
            $query->whereHas('track.subCategory.category', function ($categoryQuery) use ($category): void {
                $categoryQuery->where('slug', $category)->orWhere('name', $category);
            });
        }

        if ($subCategory = trim((string) ($filters['sub_category'] ?? ''))) {
            $query->whereHas('track.subCategory', function ($subCategoryQuery) use ($subCategory): void {
                $subCategoryQuery->where('slug', $subCategory)->orWhere('name', $subCategory);
            });
        }

        $paginator = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Course $course): array => $this->presentCourse($course))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicCourseFilters(): array
    {
        $categories = Category::query()
            ->with(['subCategories' => fn ($query) => $query
                ->where('status', 'active')
                ->orderBy('name')])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'sub_categories' => $category->subCategories
                    ->map(fn ($subCategory): array => [
                        'id' => $subCategory->id,
                        'name' => $subCategory->name,
                        'slug' => $subCategory->slug,
                        'category_id' => $subCategory->category_id,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'categories' => $categories,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'level' => $course->level,
            'duration' => $course->duration,
            'price' => $course->price,
            'language' => $course->language,
            'certificate_available' => $course->certificate_available,
            'thumbnail' => null,
            'thumbnail_url' => $this->publicImageDataUri($course->thumbnail),
            'track' => $course->track?->name,
            'sub_category' => $course->track?->subCategory?->name,
            'category' => $course->track?->subCategory?->category?->name,
            'lessons' => $course->relationLoaded('lessons')
                ? $course->lessons->map(fn ($lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'description' => $lesson->description,
                    'content' => $lesson->content,
                    'video_url' => $lesson->video_url,
                    'duration' => $lesson->duration,
                    'order_number' => $lesson->order_number,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicCourseDetail(string $slug): array
    {
        $course = Course::query()
            ->with([
                'track.subCategory.category',
                'lessons' => fn ($query) => $query
                    ->where('status', 'active')
                    ->orderBy('order_number')
                    ->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return $this->presentCourse($course);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicFeaturedNews(?int $limit = null): array
    {
        $query = News::query()
            ->with([
                'user',
                'images' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->latest('published_at')
            ->latest('id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (News $news): array => $this->presentNews($news))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function paginatedPublicNews(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);

        /** @var LengthAwarePaginator $paginator */
        $query = News::query()
            ->with([
                'user',
                'images' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('is_active', true);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query
            ->orderBy('sort_order')
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (News $news): array => $this->presentNews($news))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicNewsDetail(string $slug): array
    {
        $news = News::query()
            ->with([
                'user',
                'images' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->presentNews($news);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentNews(News $news): array
    {
        $news->loadMissing(['images', 'user']);

        return [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'author' => $news->user?->name ?? $news->user?->email,
            'excerpt' => $news->excerpt,
            'description' => $this->sanitizeContent($news->description),
            'published_at' => $news->published_at?->format('Y-m-d'),
            'sort_order' => $news->sort_order,
            'is_featured' => $news->is_featured,
            'is_active' => $news->is_active,
            'created_at' => $news->created_at?->format('Y-m-d'),
            'images' => $news->images->map(fn ($image): array => [
                'id' => $image->id,
                'image' => null,
                'image_url' => $this->publicImageDataUri($image->image),
                'position' => $image->position,
                'is_active' => $image->is_active,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicFeaturedVideos(?int $limit = null): array
    {
        $query = WebsiteVideo::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->latest('id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (WebsiteVideo $video): array => $this->presentVideo($video))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function paginatedPublicVideos(int $perPage = 12, array $filters = []): array
    {
        $perPage = min(max($perPage, 1), 24);

        /** @var LengthAwarePaginator $paginator */
        $query = WebsiteVideo::query()
            ->where('is_active', true);

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
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (WebsiteVideo $video): array => $this->presentVideo($video))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicVideoDetail(int $id): array
    {
        $video = WebsiteVideo::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->presentVideo($video);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentVideo(WebsiteVideo $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'video_url' => $video->video_url,
            'thumbnail_url' => $this->publicImageDataUri($video->thumbnail_path),
            'duration' => $video->duration,
            'views_count' => $video->views_count,
            'sort_order' => $video->sort_order,
            'is_featured' => $video->is_featured,
            'created_at' => $video->created_at?->format('Y-m-d'),
        ];
    }

    public function uniqueUploadPath(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($directory, $name, 'public');
    }

    public function publicImageDataUri(?string $path): ?string
    {
        if (! $path || Str::startsWith($path, ['data:', 'http://', 'https://', '//'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($path);

        if (! is_string($mimeType) || ! Str::startsWith($mimeType, 'image/')) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    public function deletePublicFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function sanitizeContent(?string $content): ?string
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

    /**
     * @return array<string, mixed>
     */
    public function presentPage(Page $page): array
    {
        $page->loadMissing(['hero.images', 'menus']);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $this->sanitizeContent($page->content),
            'is_active' => $page->is_active,
            'created_at' => $page->created_at?->format('Y-m-d'),
            'menus' => $page->menus->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'position' => $menu->position,
                'is_active' => $menu->is_active,
            ])->values()->all(),
            'hero' => $page->hero ? [
                'id' => $page->hero->id,
                'title' => $page->hero->title,
                'subtitle' => $page->hero->subtitle,
                'description' => $page->hero->description,
                'background_image' => null,
                'background_image_url' => $this->publicImageDataUri($page->hero->background_image),
                'primary_button_text' => $page->hero->primary_button_text,
                'primary_button_url' => $page->hero->primary_button_url,
                'secondary_button_text' => $page->hero->secondary_button_text,
                'secondary_button_url' => $page->hero->secondary_button_url,
                'overlay_opacity' => $page->hero->overlay_opacity,
                'text_alignment' => $page->hero->text_alignment,
                'is_active' => $page->hero->is_active,
                'images' => $page->hero->images->map(fn ($image): array => [
                    'id' => $image->id,
                    'image' => null,
                    'image_url' => $this->publicImageDataUri($image->image),
                    'position' => $image->position,
                    'is_active' => $image->is_active,
                ])->values()->all(),
            ] : null,
        ];
    }
}
