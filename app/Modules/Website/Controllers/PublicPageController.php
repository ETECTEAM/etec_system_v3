<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudyClass;
use App\Modules\Website\Actions\RequestClassJoin;
use App\Modules\Website\Requests\JoinClassRequest;
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

    public function joinClass(JoinClassRequest $request, StudyClass $studyClass, RequestClassJoin $requestClassJoin): RedirectResponse
    {
        $requestClassJoin->handle($studyClass, $request->validated());

        return back()->with('success', 'Your request has been sent — we will contact you soon.');
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
