<?php

namespace Tests\Feature\Building;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createBuilding(array $attributes = []): Building
    {
        return Building::create(array_merge([
            'name' => 'Building A',
        ], $attributes));
    }

    // GET /dashboard/buildings

    public function test_super_admin_can_view_buildings_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/buildings')
            ->assertOk();
    }

    public function test_admin_can_view_buildings_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/buildings')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_buildings_index(): void
    {
        $this->get('/dashboard/buildings')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_access_building_routes(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->instructor())
            ->get('/dashboard/buildings')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->post('/dashboard/buildings', ['name' => 'Blocked'])
            ->assertForbidden();
    }

    // GET /dashboard/buildings/create and /edit/{building}

    public function test_super_admin_can_view_create_and_edit_pages(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/buildings/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/buildings/edit/{$building->id}")
            ->assertOk();
    }

    // POST /dashboard/buildings

    public function test_super_admin_can_create_a_building(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/buildings', [
                'name' => 'New Tower',
                'code' => 'NT-01',
                'description' => 'Main teaching tower',
            ])
            ->assertRedirect('/dashboard/buildings')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('buildings', [
            'name' => 'New Tower',
            'code' => 'NT-01',
            'description' => 'Main teaching tower',
        ]);
    }

    public function test_admin_can_create_a_building(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/buildings', ['name' => 'Admin Wing'])
            ->assertRedirect('/dashboard/buildings');

        $this->assertDatabaseHas('buildings', ['name' => 'Admin Wing']);
    }

    public function test_store_redirects_to_wizard_step_two_when_requested(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/buildings', [
                'name' => 'Wizard Building',
                'redirect_to' => 'wizard',
            ])
            ->assertRedirect('/dashboard/buildings/create?building_id='.Building::where('name', 'Wizard Building')->first()->id.'&step=2');
    }

    public function test_store_rejects_duplicate_building_name(): void
    {
        $this->createBuilding(['name' => 'Duplicate Me']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/buildings', ['name' => 'Duplicate Me'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertSame(1, Building::where('name', 'Duplicate Me')->count());
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/buildings', ['code' => 'NO-NAME'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/buildings/{building}

    public function test_super_admin_can_update_a_building(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$building->id}", [
                'name' => 'Renamed Building',
                'code' => 'RB',
            ])
            ->assertRedirect('/dashboard/buildings')
            ->assertSessionHas('success');

        $this->assertSame('Renamed Building', $building->fresh()->name);
        $this->assertSame('RB', $building->fresh()->code);
    }

    public function test_update_allows_keeping_the_buildings_own_name(): void
    {
        $building = $this->createBuilding(['name' => 'Same Name']);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$building->id}", ['name' => 'Same Name'])
            ->assertRedirect('/dashboard/buildings');
    }

    public function test_update_rejects_another_buildings_name(): void
    {
        $this->createBuilding(['name' => 'First Building']);
        $second = $this->createBuilding(['name' => 'Second Building']);

        $this->actingAs($this->admin())
            ->putJson("/dashboard/buildings/{$second->id}", ['name' => 'First Building'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // DELETE /dashboard/buildings/{building}

    public function test_super_admin_can_delete_a_building_with_its_floors_and_rooms(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);
        $room = $floor->rooms()->create(['room_number' => '101', 'status' => 'available']);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/buildings/{$building->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
        $this->assertDatabaseMissing('floors', ['id' => $floor->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    // POST /dashboard/buildings/{building}/floors

    public function test_super_admin_can_add_a_floor_to_a_building(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/buildings/{$building->id}/floors", [
                'name' => 'Level One',
                'level' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('floors', [
            'building_id' => $building->id,
            'name' => 'Level One',
            'level' => 1,
        ]);
    }

    public function test_floor_names_must_be_unique_within_a_building(): void
    {
        $building = $this->createBuilding();
        $building->floors()->create(['name' => 'Level One', 'level' => 1]);

        $this->actingAs($this->admin())
            ->postJson("/dashboard/buildings/{$building->id}/floors", [
                'name' => 'Level One',
                'level' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertSame(1, Floor::where('building_id', $building->id)->count());
    }

    public function test_floor_levels_must_be_unique_within_a_building(): void
    {
        $building = $this->createBuilding();
        $building->floors()->create(['name' => 'Ground', 'level' => 0]);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/buildings/{$building->id}/floors", [
                'name' => 'Another Ground',
                'level' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    public function test_floor_level_must_be_in_range(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/buildings/{$building->id}/floors", [
                'name' => 'Sky Floor',
                'level' => 301,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    // POST /dashboard/buildings/{building}/floors/auto-generate

    public function test_auto_generate_creates_a_numbered_floor_sequence(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/buildings/{$building->id}/floors/auto-generate", [
                'start_name' => 'Floor 1',
                'total_floors' => 3,
                'start_level' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '3 floors created successfully.');

        foreach ([1, 2, 3] as $number) {
            $this->assertDatabaseHas('floors', [
                'building_id' => $building->id,
                'name' => "Floor {$number}",
                'level' => $number,
            ]);
        }
    }

    public function test_auto_generate_supports_letter_sequences(): void
    {
        $building = $this->createBuilding();

        $this->actingAs($this->admin())
            ->post("/dashboard/buildings/{$building->id}/floors/auto-generate", [
                'start_name' => 'A',
                'total_floors' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 floors created successfully.');

        foreach (['A', 'B'] as $letter) {
            $this->assertDatabaseHas('floors', [
                'building_id' => $building->id,
                'name' => $letter,
            ]);
        }
    }

    public function test_auto_generate_refuses_existing_floor_names(): void
    {
        $building = $this->createBuilding();
        $building->floors()->create(['name' => 'Floor 1', 'level' => 1]);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/buildings/{$building->id}/floors/auto-generate", [
                'start_name' => 'Floor 1',
                'total_floors' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_name']);
    }

    // PUT/DELETE /dashboard/buildings/{building}/floors/{floor}

    public function test_super_admin_can_rename_a_floor(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Old Name', 'level' => 2]);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$building->id}/floors/{$floor->id}", [
                'name' => 'New Name',
                'level' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('New Name', $floor->fresh()->name);
        $this->assertSame(3, $floor->fresh()->level);
    }

    public function test_updating_a_floor_from_another_building_returns_404(): void
    {
        $buildingA = $this->createBuilding(['name' => 'A']);
        $buildingB = $this->createBuilding(['name' => 'B']);
        $foreignFloor = $buildingB->floors()->create(['name' => 'Foreign', 'level' => 1]);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$buildingA->id}/floors/{$foreignFloor->id}", [
                'name' => 'Hijacked',
            ])
            ->assertNotFound();
    }

    public function test_deleting_a_floor_also_deletes_its_rooms(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Doomed', 'level' => 9]);
        $room = $floor->rooms()->create(['room_number' => '999', 'status' => 'available']);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/buildings/{$building->id}/floors/{$floor->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('floors', ['id' => $floor->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    // POST /dashboard/buildings/{building}/floors/{floor}/rooms

    public function test_super_admin_can_add_a_room_to_a_floor(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/buildings/{$building->id}/floors/{$floor->id}/rooms", [
                'room_number' => 'G01',
                'capacity' => 30,
                'status' => 'available',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'floor_id' => $floor->id,
            'room_number' => 'G01',
            'capacity' => 30,
            'status' => 'available',
        ]);
    }

    public function test_room_numbers_must_be_unique_within_a_floor(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);
        $floor->rooms()->create(['room_number' => 'G01', 'status' => 'available']);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/buildings/{$building->id}/floors/{$floor->id}/rooms", [
                'room_number' => 'G01',
                'status' => 'available',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }

    public function test_room_status_is_validated_against_allowed_values(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/buildings/{$building->id}/floors/{$floor->id}/rooms", [
                'room_number' => 'G02',
                'status' => 'demolished',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cannot_add_room_to_a_floor_of_another_building(): void
    {
        $buildingA = $this->createBuilding(['name' => 'A']);
        $buildingB = $this->createBuilding(['name' => 'B']);
        $foreignFloor = $buildingB->floors()->create(['name' => 'Foreign', 'level' => 1]);

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/buildings/{$buildingA->id}/floors/{$foreignFloor->id}/rooms", [
                'room_number' => 'X01',
                'status' => 'available',
            ])
            ->assertNotFound();
    }

    // PUT/DELETE /dashboard/buildings/{building}/floors/{floor}/rooms/{room}

    public function test_super_admin_can_update_a_nested_room(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);
        $room = $floor->rooms()->create(['room_number' => 'G01', 'capacity' => 10, 'status' => 'available']);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$building->id}/floors/{$floor->id}/rooms/{$room->id}", [
                'room_number' => 'G01-UP',
                'capacity' => 40,
                'status' => 'maintenance',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('G01-UP', $room->fresh()->room_number);
        $this->assertSame(40, $room->fresh()->capacity);
        $this->assertSame('maintenance', $room->fresh()->status);
    }

    public function test_updating_a_room_from_the_wrong_floor_returns_404(): void
    {
        $building = $this->createBuilding();
        $floorOne = $building->floors()->create(['name' => 'First', 'level' => 1]);
        $floorTwo = $building->floors()->create(['name' => 'Second', 'level' => 2]);
        $roomOnOtherFloor = $floorTwo->rooms()->create(['room_number' => '201', 'status' => 'available']);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/buildings/{$building->id}/floors/{$floorOne->id}/rooms/{$roomOnOtherFloor->id}", [
                'room_number' => 'HACKED',
                'status' => 'available',
            ])
            ->assertNotFound();
    }

    public function test_super_admin_can_delete_a_nested_room(): void
    {
        $building = $this->createBuilding();
        $floor = $building->floors()->create(['name' => 'Ground', 'level' => 0]);
        $room = $floor->rooms()->create(['room_number' => 'G09', 'status' => 'closed']);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/buildings/{$building->id}/floors/{$floor->id}/rooms/{$room->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
