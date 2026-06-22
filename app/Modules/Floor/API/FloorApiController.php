<?php

namespace App\Modules\Floor\API;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Modules\Floor\Requests\StoreFloorRequest;
use App\Modules\Floor\Requests\UpdateFloorRequest;
use App\Modules\Floor\Services\FloorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorApiController extends Controller
{
    public function __construct(
        private readonly FloorService $floorService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 10)));

        return response()->json(
            $this->floorService->paginate([
                'search' => $request->string('search')->toString(),
            ], $perPage)
        );
    }

    public function store(StoreFloorRequest $request): JsonResponse
    {
        $floor = $this->floorService->create($request->toData());

        return response()->json([
            'message' => 'Floor created successfully.',
            'data' => $this->floorService->present($floor),
        ], 201);
    }

    public function show(Floor $floor): JsonResponse
    {
        return response()->json([
            'data' => $this->floorService->present($floor),
        ]);
    }

    public function update(UpdateFloorRequest $request, Floor $floor): JsonResponse
    {
        $floor = $this->floorService->update($floor, $request->toData());

        return response()->json([
            'message' => 'Floor updated successfully.',
            'data' => $this->floorService->present($floor),
        ]);
    }

    public function destroy(Floor $floor): JsonResponse
    {
        $this->floorService->delete($floor);

        return response()->json([
            'message' => 'Floor deleted successfully.',
        ]);
    }
}
