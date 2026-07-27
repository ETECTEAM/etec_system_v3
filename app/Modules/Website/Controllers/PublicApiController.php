<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function schoolSettings(): JsonResponse
    {
        $settings = $this->website->publicSettings();

        return $this->success([
            'school_name' => $settings['school_name'],
            'school_logo_url' => $settings['logo_url'],
            'school_logo' => $settings['school_logo'],
        ]);
    }

    public function navigation(): JsonResponse
    {
        return $this->success($this->website->publicMenus());
    }

    public function home(): JsonResponse
    {
        $homePage = Page::query()
            ->with('hero.images')
            ->where('slug', 'home')
            ->where('is_active', true)
            ->first();

        return $this->success([
            'page' => $homePage ? $this->website->presentPage($homePage) : null,
            'courses' => $this->website->publicCourses(6),
            'news' => [],
            'events' => [],
            'videos' => [],
            'testimonials' => [],
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::query()
            ->with('hero.images')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->success($this->website->presentPage($page));
    }

    public function courses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'sub_category' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:80'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
            'sort_by' => ['nullable', 'string', 'in:title,level,duration,price,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        return response()->json([
            'success' => true,
            ...$this->website->paginatedPublicCourses((int) ($validated['per_page'] ?? 12), $validated),
        ]);
    }

    public function course(string $slug): JsonResponse
    {
        return $this->success($this->website->publicCourseDetail($slug));
    }

    public function emptyCollection(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 12,
                'total' => 0,
            ],
        ]);
    }

    public function emptyDetail(string $slug = ''): JsonResponse
    {
        abort(404);
    }

    public function contact(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return $this->success([
            'message' => 'Your message has been received.',
        ]);
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
