<?php

namespace Tests\Feature\Schedules;

use App\Models\ClassType;
use App\Models\Schedule;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createClassType(): ClassType
    {
        return ClassType::create(['type_name' => 'Network', 'is_active' => true]);
    }

    private function createSchedule(array $attributes = []): Schedule
    {
        $schedule = Schedule::create(array_merge([
            'class_type_id' => $this->createClassType()->class_type_id,
            'term_id' => Term::create(['term_name' => 'Mon & Tue'])->id,
        ], $attributes));

        $morning = Time::create(['time_name' => '08:00 AM - 10:00 AM']);
        $schedule->times()->sync([$morning->id]);

        return $schedule;
    }

    // GET /dashboard/schdule

    public function test_super_admin_can_view_schedules_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/schdule')
            ->assertOk();
    }

    public function test_admin_can_view_schedules_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/schdule')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_schedules(): void
    {
        $this->get('/dashboard/schdule')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_or_manage_schedules(): void
    {
        $schedule = $this->createSchedule();

        $this->actingAs($this->instructor())
            ->get('/dashboard/schdule')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/schdule', [
                'class_type_id' => 1,
                'term_id' => 1,
                'time_ids' => [1],
            ])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/schdule/{$schedule->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    // GET create/edit pages

    public function test_super_admin_can_view_schedule_create_and_edit_pages(): void
    {
        $schedule = $this->createSchedule();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/schdule/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/schdule/{$schedule->id}/edit")
            ->assertOk();
    }

    // POST /dashboard/schdule

    public function test_super_admin_can_create_a_schedule_with_time_slots(): void
    {
        $classType = $this->createClassType();
        $term = Term::create(['term_name' => 'Mon & Tue']);
        $morning = Time::create(['time_name' => '08:00 AM - 10:00 AM']);
        $afternoon = Time::create(['time_name' => '02:00 PM - 04:00 PM']);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/schdule', [
                'class_type_id' => $classType->class_type_id,
                'term_id' => $term->id,
                'time_ids' => [$morning->id, $afternoon->id],
            ])
            ->assertRedirect('/dashboard/schdule')
            ->assertSessionHas('success', 'Schedule created successfully.');

        $schedule = Schedule::where('class_type_id', $classType->class_type_id)->firstOrFail();

        $this->assertSame($term->id, $schedule->term_id);
        $this->assertEqualsCanonicalizing(
            [$morning->id, $afternoon->id],
            $schedule->times->pluck('id')->all(),
        );
    }

    public function test_admin_can_create_a_schedule(): void
    {
        $classType = $this->createClassType();
        $term = Term::create(['term_name' => 'Wed & Thu']);
        $time = Time::create(['time_name' => '06:00 PM - 08:00 PM']);

        $this->actingAs($this->admin())
            ->post('/dashboard/schdule', [
                'class_type_id' => $classType->class_type_id,
                'term_id' => $term->id,
                'time_ids' => [$time->id],
            ])
            ->assertRedirect('/dashboard/schdule');

        $this->assertDatabaseHas('schedules', [
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
        ]);
    }

    public function test_store_validates_required_fields_and_existing_references(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/schdule', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['class_type_id', 'term_id', 'time_ids']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/schdule', [
                'class_type_id' => 99999,
                'term_id' => 99999,
                'time_ids' => [99999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['class_type_id', 'term_id', 'time_ids.0']);
    }

    public function test_store_rejects_empty_time_ids_array(): void
    {
        $classType = $this->createClassType();
        $term = Term::create(['term_name' => 'Fri']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/schdule', [
                'class_type_id' => $classType->class_type_id,
                'term_id' => $term->id,
                'time_ids' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['time_ids']);
    }

    // PUT /dashboard/schdule/{schdule}

    public function test_super_admin_can_update_a_schedule_and_its_times(): void
    {
        $schedule = $this->createSchedule();
        $replacement = Time::create(['time_name' => '04:00 PM - 06:00 PM']);
        $newTerm = Term::create(['term_name' => 'Sat & Sun']);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/schdule/{$schedule->id}", [
                'class_type_id' => $schedule->class_type_id,
                'term_id' => $newTerm->id,
                'time_ids' => [$replacement->id],
            ])
            ->assertRedirect('/dashboard/schdule')
            ->assertSessionHas('success', 'Schedule updated successfully.');

        $this->assertSame($newTerm->id, $schedule->fresh()->term_id);
        $this->assertEqualsCanonicalizing([$replacement->id], $schedule->fresh()->times->pluck('id')->all());
    }

    // DELETE /dashboard/schdule/{schdule}

    public function test_super_admin_can_delete_a_schedule(): void
    {
        $schedule = $this->createSchedule();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/schdule/{$schedule->id}")
            ->assertRedirect('/dashboard/schdule')
            ->assertSessionHas('success', 'Schedule deleted successfully.');

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
        $this->assertDatabaseMissing('schedule_time', ['schedule_id' => $schedule->id]);
    }

    // DELETE /dashboard/schdule/class-type/{classTypeId}

    public function test_destroy_by_class_type_removes_every_schedule_of_that_type_only(): void
    {
        $targetType = $this->createClassType();
        Schedule::create([
            'class_type_id' => $targetType->class_type_id,
            'term_id' => Term::create(['term_name' => 'Mon & Tue'])->id,
        ]);
        Schedule::create([
            'class_type_id' => $targetType->class_type_id,
            'term_id' => Term::create(['term_name' => 'Wed & Thu'])->id,
        ]);

        $otherType = ClassType::create(['type_name' => 'Programming', 'is_active' => true]);
        $keptSchedule = Schedule::create([
            'class_type_id' => $otherType->class_type_id,
            'term_id' => Term::create(['term_name' => 'Fri'])->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/schdule/class-type/{$targetType->class_type_id}")
            ->assertRedirect('/dashboard/schdule')
            ->assertSessionHas('success', 'Schedules deleted successfully.');

        $this->assertSame(0, Schedule::where('class_type_id', $targetType->class_type_id)->count());
        $this->assertDatabaseHas('schedules', ['id' => $keptSchedule->id]);

        // The class type itself survives; only its schedules go.
        $this->assertDatabaseHas('class_type', ['class_type_id' => $targetType->class_type_id]);
    }
}
