<?php

namespace Tests\Feature\Floor;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class FloorControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createFloor(array $attributes = []): Floor
    {
        return Floor::create(array_merge([
            'name' => 'Ground',
            'level' => 0,
        ], $attributes));
    }

    // GET /dashboard/floors

    public function test_super_admin_can_view_floors_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/floors')
            ->assertOk();
    }

    public function test_admin_can_view_floors_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/floors')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_floors_index(): void
    {
        $this->get('/dashboard/floors')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_floors_pages(): void
    {
        $floor = $this->createFloor();

        foreach (['/dashboard/floors', '/dashboard/floors/data', "/dashboard/floors/{$floor->id}", "/dashboard/floors/edit/{$floor->id}"] as $uri) {
            $this->actingAs($this->instructor())
                ->getJson($uri)
                ->assertForbidden();
        }
    }

    // GET /dashboard/floors/data

    public function test_data_endpoint_returns_paginated_floors(): void
    {
        // Levels chosen away from 0: MySQL coerces a non-numeric search term to 0,
        // so FloorService's orWhere('level', $search) would match level-0 rows too.
        $this->createFloor(['name' => 'First', 'level' => 1]);
        $this->createFloor(['name' => 'Second', 'level' => 2]);

        $this->actingAs($this->admin())
            ->getJson('/dashboard/floors/data')
            ->assertOk()
            ->assertJsonStructure(['data', 'total', 'current_page', 'per_page']);

        $response = $this->actingAs($this->admin())
            ->getJson('/dashboard/floors/data?search=Second');

        $names = collect($response->json('data'))->pluck('name');

        $this->assertContains('Second', $names);
        $this->assertNotContains('First', $names);
    }

    public function test_data_endpoint_supports_per_page_all(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->createFloor(['name' => "Floor {$i}", 'level' => $i]);
        }

        $response = $this->actingAs($this->superAdmin())
            ->getJson('/dashboard/floors/data?per_page=all')
            ->assertOk();

        $this->assertSame(15, $response->json('total'));
        $this->assertCount(15, $response->json('data'));
    }

    // GET create/show/edit pages

    public function test_super_admin_can_view_floor_create_show_and_edit_pages(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/floors/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/floors/{$floor->id}")
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/floors/edit/{$floor->id}")
            ->assertOk();
    }

    // POST /dashboard/floors

    public function test_store_creates_an_unattached_floor_and_redirects_for_web_requests(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/floors', ['name' => 'Mezzanine', 'level' => 5])
            ->assertRedirect('/dashboard/floors')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('floors', [
            'name' => 'Mezzanine',
            'level' => 5,
            'building_id' => null,
        ]);
    }

    public function test_json_store_returns_201_with_payload(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard/floors', ['name' => 'JSON Floor'])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Floor created successfully.')
            ->assertJsonPath('data.name', 'JSON Floor');
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/floors', ['level' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/floors/{floor}

    public function test_update_renames_a_floor(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/floors/{$floor->id}", ['name' => 'Renamed', 'level' => 7])
            ->assertRedirect('/dashboard/floors')
            ->assertSessionHas('success');

        $this->assertSame('Renamed', $floor->fresh()->name);
        $this->assertSame(7, $floor->fresh()->level);
    }

    public function test_json_update_returns_updated_payload(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->admin())
            ->putJson("/dashboard/floors/{$floor->id}", ['name' => 'Via JSON'])
            ->assertOk()
            ->assertJsonPath('message', 'Floor updated successfully.')
            ->assertJsonPath('data.name', 'Via JSON');
    }

    // DELETE /dashboard/floors/{floor}

    public function test_destroy_deletes_a_floor(): void
    {
        $floor = $this->createFloor();

        $this->actingAs($this->superAdmin())
            ->deleteJson("/dashboard/floors/{$floor->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Floor deleted successfully.');

        $this->assertDatabaseMissing('floors', ['id' => $floor->id]);
    }
}
