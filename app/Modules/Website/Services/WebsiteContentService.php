<?php

namespace App\Modules\Website\Services;

use App\Models\Category;
use App\Models\Course;
use App\Models\Menu;
use App\Models\Page;
use App\Models\SchoolSetting;
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
            ->with('page:id,title,slug,is_active')
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
            'school_logo' => $settings->school_logo,
            'logo_url' => $settings->logo_url,
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
            'thumbnail' => $course->thumbnail,
            'thumbnail_url' => $course->thumbnail ? '/storage/'.ltrim($course->thumbnail, '/') : null,
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

    public function uniqueUploadPath(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($directory, $name, 'public');
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
                'background_image' => $page->hero->background_image,
                'background_image_url' => $page->hero->background_image_url,
                'primary_button_text' => $page->hero->primary_button_text,
                'primary_button_url' => $page->hero->primary_button_url,
                'secondary_button_text' => $page->hero->secondary_button_text,
                'secondary_button_url' => $page->hero->secondary_button_url,
                'overlay_opacity' => $page->hero->overlay_opacity,
                'text_alignment' => $page->hero->text_alignment,
                'is_active' => $page->hero->is_active,
                'images' => $page->hero->images->map(fn ($image): array => [
                    'id' => $image->id,
                    'image' => $image->image,
                    'image_url' => $image->image_url,
                    'position' => $image->position,
                    'is_active' => $image->is_active,
                ])->values()->all(),
            ] : null,
        ];
    }
}
