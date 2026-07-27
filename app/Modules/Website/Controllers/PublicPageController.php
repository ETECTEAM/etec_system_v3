<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $page = Page::query()
            ->with('hero')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $isCoursePage = in_array($slug, ['course', 'courses'], true);

        return Inertia::render('frontend/pages/Show', [
            'pageData' => $this->website->presentPage($page),
            'preview' => false,
            'courses' => $isCoursePage ? $this->website->paginatedPublicCourses(12, $this->courseFilters($request)) : [
                'data' => [],
                'meta' => null,
            ],
            'courseFilters' => $isCoursePage ? $this->website->publicCourseFilters() : [
                'categories' => [],
            ],
            'activeCourseFilters' => $this->courseFilters($request),
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        return response()->json($this->website->paginatedPublicCourses(12, $this->courseFilters($request)));
    }

    public function courseDetail(string $slug): Response
    {
        return Inertia::render('frontend/courses/Show', [
            'course' => $this->website->publicCourseDetail($slug),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function courseFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'sub_category' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
