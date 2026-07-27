<?php

namespace App\Modules\Website\Services;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Course;
use App\Models\SchoolSetting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
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
    public function paginatedPublicCourses(int $perPage = 12): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = Course::query()
            ->with('track.subCategory.category')
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate($perPage);

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
    private function presentCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'level' => $course->level,
            'price' => $course->price,
            'language' => $course->language,
            'certificate_available' => $course->certificate_available,
            'thumbnail' => $course->thumbnail,
            'thumbnail_url' => $course->thumbnail ? '/storage/'.ltrim($course->thumbnail, '/') : null,
            'track' => $course->track?->name,
            'sub_category' => $course->track?->subCategory?->name,
            'category' => $course->track?->subCategory?->category?->name,
        ];
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
        $page->loadMissing(['hero', 'menus']);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
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
            ] : null,
        ];
    }
}
