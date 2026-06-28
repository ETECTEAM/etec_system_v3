<?php

namespace App\Modules\building\Controller;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Modules\building\Requests\StoreBuildingRequest;
use App\Modules\building\Requests\UpdateBuildingRequest;
use App\Modules\building\Services\BuildingService;
use App\Modules\Floor\Requests\StoreFloorRequest;
use App\Modules\Floor\Requests\BulkStoreFloorRequest;
use App\Modules\Floor\Requests\UpdateFloorRequest;
use App\Modules\Room\Requests\StoreRoomRequest;
use App\Modules\Room\Requests\UpdateRoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function __construct(
        private readonly BuildingService $buildingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        return Inertia::render('backend/buildings/Index', [
            'buildings' => $this->buildingService->hierarchy(),
            'summary' => $this->buildingService->summary(),
        ]);
    }

    public function filter(Request $request): Response
    {
        $this->authorizeAccess($request);

        return Inertia::render('backend/buildings/Filter', [
            'buildings' => $this->buildingService->hierarchy(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeAccess($request);

        $building = null;
        if ($request->has('building_id')) {
            $buildingModel = Building::with('floors.rooms')->find($request->input('building_id'));
            if ($buildingModel) {
                $building = $this->buildingService->presentBuilding($buildingModel);
            }
        }

        return Inertia::render('backend/buildings/Create', [
            'building' => $building,
            'initialStep' => (int) $request->input('step', 1),
        ]);
    }

    public function edit(Request $request, Building $building): Response
    {
        $this->authorizeAccess($request);

        return Inertia::render('backend/buildings/Edit', [
            'building' => $this->buildingService->presentBuilding($building->load('floors.rooms')),
        ]);
    }

    public function store(StoreBuildingRequest $request): RedirectResponse
    {
        $building = $this->buildingService->create($request->toData());

        if ($request->input('redirect_to') === 'wizard') {
            return redirect('/dashboard/buildings/create?building_id='.$building->id.'&step=2')->with('success', 'Building created successfully.');
        }

        return redirect('/dashboard/buildings')->with('success', 'Building created successfully.');
    }

    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        $this->buildingService->update($building, $request->toData());

        if ($request->input('redirect_to') === 'wizard') {
            return redirect('/dashboard/buildings/create?building_id='.$building->id.'&step=2')->with('success', 'Building updated successfully.');
        }

        return redirect('/dashboard/buildings')->with('success', 'Building updated successfully.');
    }

    public function destroy(Request $request, Building $building): RedirectResponse
    {
        $this->authorizeAccess($request);
        $this->buildingService->delete($building);

        return back()->with('success', 'Building deleted successfully.');
    }

    public function storeFloor(StoreFloorRequest $request, Building $building): RedirectResponse
    {
        $data = $request->toData();
        $this->buildingService->createFloor($building, $data);

        return back()->with('success', 'Floor created successfully.');
    }

    public function bulkStoreFloor(BulkStoreFloorRequest $request, Building $building): RedirectResponse
    {
        $payload = $request->payload();

        $createdFloors = $this->buildingService->createFloorSequence(
            $building,
            $payload['start_name'],
            $payload['total_floors'],
            $payload['start_level'],
        );

        return back()->with('success', $createdFloors->count().' floors created successfully.');
    }

    public function updateFloor(UpdateFloorRequest $request, Building $building, Floor $floor): RedirectResponse
    {
        $data = $request->toData();
        $this->buildingService->updateFloor($building, $floor, $data);

        return back()->with('success', 'Floor updated successfully.');
    }

    public function destroyFloor(Request $request, Building $building, Floor $floor): RedirectResponse
    {
        $this->authorizeAccess($request);
        $this->buildingService->deleteFloor($building, $floor);

        return back()->with('success', 'Floor deleted successfully.');
    }

    public function storeRoom(StoreRoomRequest $request, Building $building, Floor $floor): RedirectResponse
    {
        $data = $request->toData();
        $this->buildingService->createRoom($building, $floor, $data);

        return back()->with('success', 'Room created successfully.');
    }

    public function updateRoom(UpdateRoomRequest $request, Building $building, Floor $floor, Room $room): RedirectResponse
    {
        $data = $request->toData();
        $this->buildingService->updateRoom($building, $floor, $room, $data);

        return back()->with('success', 'Room updated successfully.');
    }

    public function destroyRoom(Request $request, Building $building, Floor $floor, Room $room): RedirectResponse
    {
        $this->authorizeAccess($request);
        $this->buildingService->deleteRoom($building, $floor, $room);

        return back()->with('success', 'Room deleted successfully.');
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin')
                || $request->user()?->hasRole('instructor'),
            403
        );
    }
}
