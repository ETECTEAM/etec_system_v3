<?php

namespace App\Modules\Room\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Modules\Room\Requests\BulkStoreRoomRequest;
use App\Modules\Room\Requests\StoreRoomRequest;
use App\Modules\Room\Requests\UpdateRoomRequest;
use App\Modules\Room\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function index(): Response
    {
        return Inertia::render('backend/buildings/Room');
    }

    public function paginatedIndex(Request $request): JsonResponse
    {
        $rooms = $this->roomService->paginateRooms([
            'search' => $request->string('search')->toString(),
        ], 5);

        return response()->json($rooms);
    }

    public function create(): Response
    {
        return Inertia::render('backend/buildings/RoomCreate');
    }

    public function show(Room $room): Response
    {
        return Inertia::render('backend/rooms/Show', [
            'room' => $this->roomService->presentRoom($room),
        ]);
    }

    public function edit(Room $room): Response
    {
        return Inertia::render('backend/buildings/RoomUpdate', [
            'room' => $this->roomService->presentRoom($room),
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $this->roomService->create($request->toData());

        return redirect('/dashboard/rooms')->with('success', 'Room created successfully.');
    }

    public function bulkStore(BulkStoreRoomRequest $request): RedirectResponse
    {
        $payload = $request->payload();

        $createdRooms = $this->roomService->createSequence(
            $payload['floor_id'],
            $payload['start_room_number'],
            $payload['total_rooms'],
            $payload['capacity'],
            $payload['status'],
        );

        if ($request->boolean('redirect_back')) {
            return back()->with('success', $createdRooms->count().' rooms created successfully.');
        }

        return redirect('/dashboard/rooms')->with('success', $createdRooms->count().' rooms created successfully.');
    }


    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $this->roomService->update($room, $request->toData());

        return redirect('/dashboard/rooms')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->roomService->delete($room);

        return redirect('/dashboard/rooms')->with('success', 'Room deleted successfully.');
    }
}
