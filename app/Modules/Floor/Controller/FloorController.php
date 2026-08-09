<?php

namespace App\Modules\Floor\Controller;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Modules\Floor\Requests\StoreFloorRequest;
use App\Modules\Floor\Requests\UpdateFloorRequest;
use App\Modules\Floor\Services\FloorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FloorController extends Controller
{
    public function __construct(
        private readonly FloorService $floorService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeFloorAccess($request);

        return Inertia::render('backend/buildings/Floor/FloorIndex');
    }

    public function paginatedIndex(Request $request): JsonResponse
    {
        $this->authorizeFloorAccess($request);
        $perPage = $this->resolvePerPage($request);

        $floors = $this->floorService->paginate([
            'search' => $request->string('search')->toString(),
        ], $perPage);

        return response()->json($floors);
    }

    public function create(Request $request): Response
    {
        $this->authorizeFloorAccess($request);

        return Inertia::render('backend/buildings/Floor/FloorCreate');
    }

    public function store(StoreFloorRequest $request): JsonResponse|RedirectResponse
    {
        $floor = $this->floorService->create($request->toData());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Floor created successfully.',
                'data' => $this->floorService->present($floor),
            ], 201);
        }

        return redirect('/dashboard/floors')->with('success', 'Floor created successfully.');
    }

    public function show(Request $request, Floor $floor): Response
    {
        $this->authorizeFloorAccess($request);

        return Inertia::render('backend/buildings/Floor/FloorShow', [
            'floor' => $this->floorService->present($floor),
        ]);
    }

    public function edit(Request $request, Floor $floor): Response
    {
        $this->authorizeFloorAccess($request);

        return Inertia::render('backend/buildings/Floor/FloorEdit', [
            'floor' => $this->floorService->present($floor),
        ]);
    }

    public function update(UpdateFloorRequest $request, Floor $floor): JsonResponse|RedirectResponse
    {
        $floor = $this->floorService->update($floor, $request->toData());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Floor updated successfully.',
                'data' => $this->floorService->present($floor),
            ]);
        }

        return redirect('/dashboard/floors')->with('success', 'Floor updated successfully.');
    }

    public function destroy(Request $request, Floor $floor): JsonResponse|RedirectResponse
    {
        $this->authorizeFloorAccess($request);

        $this->floorService->delete($floor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Floor deleted successfully.',
            ]);
        }

        return redirect('/dashboard/floors')->with('success', 'Floor deleted successfully.');
    }

    private function authorizeFloorAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin')
                || $request->user()?->hasRole('instructor'),
            403
        );
    }

    private function resolvePerPage(Request $request): int
    {
        if ($request->input('per_page') === 'all') {
            return max(1, Floor::query()->count());
        }

        return max(1, min(1000, (int) $request->integer('per_page', 10)));
    }
}
