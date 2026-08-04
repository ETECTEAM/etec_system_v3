<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyClass;
use App\Modules\Website\Actions\RegisterClassStudent;
use App\Modules\Website\Requests\RegisterClassRequest;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function classes(Request $request): Response
    {
        return Inertia::render('frontend/classes/Index', [
            'classes' => $this->website->paginatedPublicClasses(12, $this->classFilters($request)),
            'activeClassFilters' => $this->classFilters($request),
        ]);
    }

    public function classesLoadMore(Request $request): JsonResponse
    {
        return response()->json($this->website->paginatedPublicClasses(12, $this->classFilters($request)));
    }

    public function registerForClass(RegisterClassRequest $request, StudyClass $studyClass, RegisterClassStudent $registerClassStudent): RedirectResponse
    {
        $registerClassStudent->handle($studyClass, $request->validated());

        return back()->with('success', "You're registered! We'll be in touch about payment and class details.");
    }

    /**
     * @return array<string, mixed>
     */
    private function classFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'class_type' => ['nullable', 'string', 'in:physical,online'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
