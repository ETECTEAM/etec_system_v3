<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorScheduleBlockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([PermissionSeeder::class, RoleSeeder::class, AssignPermissionSeeder::class]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('super_admin');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');

        return $user;
    }

    private function instructorWithMondayShift(): InstructorData
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        $instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Test Instructor',
            'available_for_class' => true,
            'status' => true,
        ]);

        InstructorAvailability::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'employment_type' => 'full_time',
            'shift_group' => 'custom',
            'period' => 'daytime',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);

        return $instructor;
    }

    private function time(string $name): Time
    {
        $term = Term::firstOrCreate(['term_name' => 'Mon & Thu']);

        return Time::create(['term_id' => $term->id, 'time_name' => $name]);
    }

    // 1. Working ShiftTemplate slot can be manually blocked.
    public function test_working_slot_can_be_manually_blocked(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $response = $this->actingAs($instructor->user)
            ->postJson('/dashboard/instructor-schedule-blocks', [
                'day_of_week' => 1,
                'time_id' => $time->id,
                'reason' => 'Old system class',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('instructor_schedule_blocks', [
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'reason' => 'Old system class',
            'status' => 'active',
        ]);
    }

    // 4. Non-working ShiftTemplate slot remains unavailable (rejects the block).
    public function test_non_working_slot_cannot_be_manually_blocked(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('05:00 PM - 06:15 PM'); // outside the 08:00-12:00 window

        $response = $this->actingAs($instructor->user)
            ->postJson('/dashboard/instructor-schedule-blocks', [
                'day_of_week' => 1,
                'time_id' => $time->id,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('instructor_schedule_blocks', [
            'instructor_id' => $instructor->id,
            'time_id' => $time->id,
        ]);
    }

    // 6. Duplicate manual block is rejected.
    public function test_duplicate_active_block_is_rejected(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor->user)
            ->postJson('/dashboard/instructor-schedule-blocks', [
                'day_of_week' => 1,
                'time_id' => $time->id,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('instructor_schedule_blocks', 1);
    }

    // 5. Removing manual block makes the working slot available again.
    public function test_removing_a_block_deletes_it(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $block = InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor->user)
            ->deleteJson("/dashboard/instructor-schedule-blocks/block/{$block->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('instructor_schedule_blocks', ['id' => $block->id]);
    }

    // 7. Manual block does not modify ShiftTemplate/InstructorAvailability.
    public function test_creating_a_block_does_not_modify_instructor_availability(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');
        $before = InstructorAvailability::where('instructor_id', $instructor->id)->get()->toArray();

        $this->actingAs($instructor->user)
            ->postJson('/dashboard/instructor-schedule-blocks', [
                'day_of_week' => 1,
                'time_id' => $time->id,
            ])
            ->assertCreated();

        $after = InstructorAvailability::where('instructor_id', $instructor->id)->get()->toArray();
        $this->assertEquals($before, $after);
    }

    public function test_admin_cannot_view_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/instructor-schedule-blocks')
            ->assertForbidden();
    }

    public function test_super_admin_cannot_view_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/instructor-schedule-blocks')
            ->assertForbidden();
    }

    public function test_instructor_can_view_index(): void
    {
        $instructor = $this->instructorWithMondayShift();

        $this->actingAs($instructor->user)
            ->get('/dashboard/instructor-schedule-blocks')
            ->assertOk();
    }

    public function test_instructor_can_fetch_schedule_data(): void
    {
        $instructor = $this->instructorWithMondayShift();

        $response = $this->actingAs($instructor->user)
            ->getJson('/dashboard/instructor-schedule-blocks/data');

        $response->assertOk();
        $response->assertJsonStructure(['schedule']);
    }
}
