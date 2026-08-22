<?php

namespace Tests\Feature\Times;

use App\Models\Time;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class TimeControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createTime(string $name = '08:00 AM - 10:00 AM'): Time
    {
        return Time::create(['time_name' => $name]);
    }

    // GET /dashboard/times

    public function test_super_admin_can_view_times_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/times')
            ->assertOk();
    }

    public function test_admin_can_view_times_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/times')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_times(): void
    {
        $this->get('/dashboard/times')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_or_manage_times(): void
    {
        $time = $this->createTime();

        $this->actingAs($this->instructor())
            ->get('/dashboard/times')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/times', ['time_name' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/times/{$time->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('times', ['id' => $time->id]);
    }

    // GET create/edit pages

    public function test_super_admin_can_view_time_create_and_edit_pages(): void
    {
        $time = $this->createTime();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/times/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/times/{$time->id}/edit")
            ->assertOk();
    }

    // POST /dashboard/times

    public function test_super_admin_can_create_a_time_slot(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/times', ['time_name' => '10:00 AM - 12:00 PM'])
            ->assertRedirect('/dashboard/times')
            ->assertSessionHas('success', 'Time created successfully.');

        $this->assertDatabaseHas('times', ['time_name' => '10:00 AM - 12:00 PM']);
    }

    public function test_admin_can_create_a_time_slot(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/times', ['time_name' => 'Evening 6-8 PM'])
            ->assertRedirect('/dashboard/times');

        $this->assertDatabaseHas('times', ['time_name' => 'Evening 6-8 PM']);
    }

    public function test_store_requires_time_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/times', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['time_name']);
    }

    // PUT /dashboard/times/{time}

    public function test_super_admin_can_update_a_time_slot(): void
    {
        $time = $this->createTime();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/times/{$time->id}", ['time_name' => '02:00 PM - 04:00 PM'])
            ->assertRedirect('/dashboard/times')
            ->assertSessionHas('success', 'Time updated successfully.');

        $this->assertSame('02:00 PM - 04:00 PM', $time->fresh()->time_name);
    }

    // DELETE /dashboard/times/{time}

    public function test_super_admin_can_delete_a_time_slot(): void
    {
        $time = $this->createTime();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/times/{$time->id}")
            ->assertRedirect('/dashboard/times')
            ->assertSessionHas('success', 'Time deleted successfully.');

        $this->assertDatabaseMissing('times', ['id' => $time->id]);
    }
}
