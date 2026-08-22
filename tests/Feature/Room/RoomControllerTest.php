<?php

namespace Tests\Feature\Room;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createFloor(): Floor
    {
        return Floor::create(['name' => 'Ground', 'level' => 0]);
    }

    private function createRoom(array $attributes = []): Room
    {
        return Room::create(array_merge([
            'floor_id' => $this->createFloor()->id,
            'room_number' => '101',
            'capacity' => 20,
            'status' => 'available',
        ], $attributes));
    }

    // GET /dashboard/rooms

    public function test_super_admin_can_view_rooms_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/rooms')
            ->assertOk();
    }

    public function test_admin_can_view_rooms_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/rooms')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_rooms_index(): void
    {
        $this->get('/dashboard/rooms')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_room_pages(): void
    {
        $room = $this->createRoom();

        foreach (['/dashboard/rooms', '/dashboard/rooms/data', "/dashboard/rooms/{$room->id}"] as $uri) {
            $this->actingAs($this->instructor())
                ->getJson($uri)
                ->assertForbidden();
        }
    }

    // GET /dashboard/rooms/data

    public function test_data_endpoint_returns_paginated_rooms_with_floor_relation(): void
    {
        $room = $this->createRoom(['room_number' => 'A-100']);

        $response = $this->actingAs($this->admin())
            ->getJson('/dashboard/rooms/data')
            ->assertOk()
            ->assertJsonStructure(['data', 'total', 'current_page', 'per_page']);

        $first = collect($response->json('data'))->firstWhere('id', $room->id);

        $this->assertNotNull($first);
        $this->assertSame('A-100', $first['room_number']);
        $this->assertArrayHasKey('floor', $first);
        $this->assertSame('Ground', $first['floor']['name']);
    }

    public function test_data_endpoint_searches_by_room_number_and_status(): void
    {
        $this->createRoom(['room_number' => 'B-200', 'status' => 'occupied']);
        $target = $this->createRoom(['room_number' => 'C-300']);

        $byNumber = $this->actingAs($this->admin())
            ->getJson('/dashboard/rooms/data?search=C-30');

        $numbers = collect($byNumber->json('data'))->pluck('room_number');
        $this->assertContains('C-300', $numbers);
        $this->assertNotContains('B-200', $numbers);

        $byStatus = $this->actingAs($this->admin())
            ->getJson('/dashboard/rooms/data?search=occupi');

        $this->assertSame(1, $byStatus->json('total'));
        $this->assertSame('B-200', $byStatus->json('data.0.room_number'));
        $this->assertSame($target->id, $target->fresh()->id);
    }

    // GET create/show/edit pages

    public function test_super_admin_can_view_room_create_show_and_edit_pages(): void
    {
        $room = $this->createRoom();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/rooms/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/rooms/{$room->id}")
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/rooms/edit/{$room->id}")
            ->assertOk();
    }

    // POST /dashboard/rooms

    public function test_super_admin_can_create_a_room(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/rooms', [
                'floor_id' => $floor->id,
                'room_number' => 'G-01',
                'capacity' => 25,
                'status' => 'available',
            ])
            ->assertRedirect('/dashboard/rooms')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'floor_id' => $floor->id,
            'room_number' => 'G-01',
            'capacity' => 25,
        ]);
    }

    public function test_admin_can_create_a_room(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->admin())
            ->post('/dashboard/rooms', [
                'floor_id' => $floor->id,
                'room_number' => 'G-02',
                'status' => 'maintenance',
            ])
            ->assertRedirect('/dashboard/rooms');

        $this->assertDatabaseHas('rooms', [
            'floor_id' => $floor->id,
            'room_number' => 'G-02',
            'status' => 'maintenance',
        ]);
    }

    public function test_store_rejects_duplicate_room_number_on_the_same_floor(): void
    {
        $floor = $this->createFloor();
        $this->createRoom(['floor_id' => $floor->id, 'room_number' => 'DUP']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/rooms', [
                'floor_id' => $floor->id,
                'room_number' => 'DUP',
                'status' => 'available',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }

    public function test_same_room_number_is_allowed_on_different_floors(): void
    {
        $floorOne = $this->createFloor();
        $floorTwo = Floor::create(['name' => 'Second', 'level' => 2]);
        $this->createRoom(['floor_id' => $floorOne->id, 'room_number' => 'TWIN']);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/rooms', [
                'floor_id' => $floorTwo->id,
                'room_number' => 'TWIN',
                'status' => 'available',
            ])
            ->assertRedirect('/dashboard/rooms');

        $this->assertSame(2, Room::where('room_number', 'TWIN')->count());
    }

    public function test_store_validates_status_against_allowed_values(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/rooms', [
                'floor_id' => $floor->id,
                'room_number' => 'X-01',
                'status' => 'burned-down',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_store_requires_room_number(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/rooms', ['status' => 'available'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }

    // POST /dashboard/rooms/auto-generate

    public function test_auto_generate_creates_a_room_sequence(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/rooms/auto-generate', [
                'floor_id' => $floor->id,
                'start_room_number' => '201',
                'total_rooms' => 3,
                'capacity' => 15,
                'status' => 'available',
            ])
            ->assertRedirect('/dashboard/rooms')
            ->assertSessionHas('success', '3 rooms created successfully.');

        foreach (['201', '202', '203'] as $number) {
            $this->assertDatabaseHas('rooms', [
                'floor_id' => $floor->id,
                'room_number' => $number,
                'capacity' => 15,
            ]);
        }
    }

    public function test_auto_generate_keeps_prefix_and_pads_numbers(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->admin())
            ->post('/dashboard/rooms/auto-generate', [
                'floor_id' => $floor->id,
                'start_room_number' => 'R-08',
                'total_rooms' => 2,
                'status' => 'available',
            ])
            ->assertRedirect('/dashboard/rooms');

        $numbers = Room::where('floor_id', $floor->id)->orderBy('id')->pluck('room_number')->all();

        $this->assertSame(['R-08', 'R-09'], $numbers);
    }

    public function test_auto_generate_redirects_back_when_requested(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/rooms/auto-generate', [
                'floor_id' => $floor->id,
                'start_room_number' => '301',
                'total_rooms' => 1,
                'status' => 'available',
                'redirect_back' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '1 rooms created successfully.');
    }

    public function test_auto_generate_refuses_existing_room_numbers(): void
    {
        $floor = $this->createFloor();
        $this->createRoom(['floor_id' => $floor->id, 'room_number' => '401']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/rooms/auto-generate', [
                'floor_id' => $floor->id,
                'start_room_number' => '400',
                'total_rooms' => 2,
                'status' => 'available',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_room_number']);

        $this->assertSame(1, Room::where('floor_id', $floor->id)->count());
    }

    // PUT /dashboard/rooms/{room}

    public function test_super_admin_can_update_a_room(): void
    {
        $room = $this->createRoom();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/rooms/{$room->id}", [
                'room_number' => 'UP-1',
                'capacity' => 50,
                'status' => 'closed',
            ])
            ->assertRedirect('/dashboard/rooms')
            ->assertSessionHas('success');

        $this->assertSame('UP-1', $room->fresh()->room_number);
        $this->assertSame(50, $room->fresh()->capacity);
        $this->assertSame('closed', $room->fresh()->status);
    }

    // DELETE /dashboard/rooms/{room}

    public function test_super_admin_can_delete_a_room(): void
    {
        $room = $this->createRoom();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/rooms/{$room->id}")
            ->assertRedirect('/dashboard/rooms')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
