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

    public function show(string $slug): Response
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
            'courses' => $isCoursePage ? $this->website->paginatedPublicCourses(12) : [
                'data' => [],
                'meta' => null,
            ],
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json($this->website->paginatedPublicCourses(12));
    }
}
