<?php

namespace Tests\Feature\Instructor;

use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleTime;
use App\Modules\Instructor\Services\InstructorProfileService;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorAvailabilityAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([PermissionSeeder::class, RoleSeeder::class, AssignPermissionSeeder::class]);
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
            'full_name' => 'Grid Instructor',
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
            'source' => InstructorAvailability::SOURCE_SCHEDULE,
        ]);

        return $instructor;
    }

    private function time(string $name): Time
    {
        $term = Term::firstOrCreate(['term_name' => 'Mon & Thu']);

        return Time::create(['term_id' => $term->id, 'time_name' => $name]);
    }

    public function test_admin_can_block_another_instructors_working_slot(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $this->actingAs($admin)
            ->postJson('/dashboard/instructor-availability/block', [
                'instructor_id' => $instructor->id,
                'day_of_week' => 1,
                'time_id' => $time->id,
                'reason' => 'Covering front desk',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('instructor_schedule_blocks', [
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'reason' => 'Covering front desk',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    public function test_instructor_cannot_use_the_admin_routes(): void
    {
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $this->actingAs($instructor->user)
            ->postJson('/dashboard/instructor-availability/block', [
                'instructor_id' => $instructor->id,
                'day_of_week' => 1,
                'time_id' => $time->id,
            ])
            ->assertForbidden();

        $this->actingAs($instructor->user)
            ->getJson('/dashboard/instructor-availability/data')
            ->assertForbidden();
    }

    public function test_admin_cannot_block_a_slot_outside_the_working_window(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('05:00 PM - 06:15 PM'); // outside 08:00-12:00

        $this->actingAs($admin)
            ->postJson('/dashboard/instructor-availability/block', [
                'instructor_id' => $instructor->id,
                'day_of_week' => 1,
                'time_id' => $time->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('instructor_schedule_blocks', [
            'instructor_id' => $instructor->id,
            'time_id' => $time->id,
        ]);
    }

    public function test_admin_cannot_block_a_slot_with_an_open_class(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $course = Course::create(['title' => 'Basic IT', 'slug' => 'basic-it-grid', 'status' => 'active']);

        StudyClass::create([
            'title' => 'Basic IT',
            'course_id' => $course->id,
            'teacher_id' => $instructor->user_id,
            'term_id' => Term::firstOrCreate(['term_name' => 'Mon & Thu'])->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 12,
            'price' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson('/dashboard/instructor-availability/block', [
                'instructor_id' => $instructor->id,
                'day_of_week' => 1,
                'time_id' => $time->id,
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_open_a_not_working_slot_and_it_survives_schedule_regeneration(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('05:00 PM - 06:15 PM'); // outside the Monday 08:00-12:00 window

        $this->actingAs($admin)
            ->postJson('/dashboard/instructor-availability/open', [
                'instructor_id' => $instructor->id,
                'day_of_week' => 1,
                'time_id' => $time->id,
            ])
            ->assertCreated();

        $adminRow = InstructorAvailability::where('instructor_id', $instructor->id)
            ->where('source', InstructorAvailability::SOURCE_ADMIN)
            ->firstOrFail();

        $this->assertSame('17:00:00', $adminRow->start_time);
        $this->assertSame('18:15:00', $adminRow->end_time);

        // Regenerating the schedule-derived rows must not touch the admin row.
        app(InstructorProfileService::class)->generateInstructorAvailabilities($instructor->fresh());

        $this->assertDatabaseHas('instructor_availabilities', [
            'id' => $adminRow->id,
            'source' => InstructorAvailability::SOURCE_ADMIN,
        ]);
    }

    public function test_close_slot_refuses_a_schedule_row_but_removes_an_admin_row(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();

        $scheduleRow = InstructorAvailability::where('instructor_id', $instructor->id)
            ->where('source', InstructorAvailability::SOURCE_SCHEDULE)
            ->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson("/dashboard/instructor-availability/open/{$scheduleRow->id}")
            ->assertStatus(422);

        $adminRow = InstructorAvailability::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 2,
            'employment_type' => 'full_time',
            'shift_group' => 'admin_override',
            'period' => 'morning',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'is_active' => true,
            'source' => InstructorAvailability::SOURCE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/dashboard/instructor-availability/open/{$adminRow->id}")
            ->assertOk();

        $this->assertDatabaseMissing('instructor_availabilities', ['id' => $adminRow->id]);
        $this->assertDatabaseHas('instructor_availabilities', ['id' => $scheduleRow->id]);
    }

    public function test_admin_can_unblock_a_slot(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();
        $time = $this->time('09:00 AM - 10:30 AM');

        $block = InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/dashboard/instructor-availability/block/{$block->id}")
            ->assertOk();

        $this->assertDatabaseMissing('instructor_schedule_blocks', ['id' => $block->id]);
    }

    public function test_admin_can_toggle_available_for_class(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();

        $this->actingAs($admin)
            ->patchJson("/dashboard/instructor-availability/instructor/{$instructor->id}", [
                'available_for_class' => false,
            ])
            ->assertOk()
            ->assertJson(['available_for_class' => false]);

        $this->assertFalse($instructor->fresh()->available_for_class);
    }

    public function test_data_endpoint_returns_the_grid_for_an_admin(): void
    {
        $admin = $this->admin();
        $this->instructorWithMondayShift();

        $this->actingAs($admin)
            ->getJson('/dashboard/instructor-availability/data')
            ->assertOk()
            ->assertJsonStructure([
                'instructors' => [['id', 'full_name', 'available_for_class', 'days' => [['day_of_week', 'slots']]]],
            ]);
    }

    public function test_each_day_shows_only_the_slots_that_day_actually_runs(): void
    {
        $admin = $this->admin();
        $instructor = $this->instructorWithMondayShift();

        $weekdayShort = $this->time('09:00 AM - 10:30 AM');
        $weekdayLong = $this->time('09:00 AM - 11:00 AM'); // same start - must collapse
        $weekendBlock = $this->time('08:00 AM - 11:00 AM');

        $schedule = WorkSchedule::create(['name' => 'Test', 'code' => 'test_ws', 'is_active' => true]);
        WorkScheduleTime::insert([
            ['work_schedule_id' => $schedule->id, 'day_of_week' => 1, 'time_id' => $weekdayShort->id],
            ['work_schedule_id' => $schedule->id, 'day_of_week' => 1, 'time_id' => $weekdayLong->id],
            ['work_schedule_id' => $schedule->id, 'day_of_week' => 6, 'time_id' => $weekendBlock->id],
        ]);

        $days = collect(
            $this->actingAs($admin)
                ->getJson('/dashboard/instructor-availability/data')
                ->assertOk()
                ->json('instructors.0.days')
        );

        $monday = collect($days->firstWhere('day_of_week', 1)['slots']);
        $friday = $days->firstWhere('day_of_week', 5)['slots'];
        $saturday = collect($days->firstWhere('day_of_week', 6)['slots']);

        // Monday collapses the two 09:00 records to the shorter one.
        $this->assertSame([$weekdayShort->id], $monday->pluck('time_id')->all());
        // Friday has no work-schedule times at all.
        $this->assertSame([], $friday);
        // Saturday runs its own wide block, not the weekday slots.
        $this->assertSame([$weekendBlock->id], $saturday->pluck('time_id')->all());
    }
}
