<?php

namespace Tests\Unit\Enroll;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\Course;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Actions\CreateStudyClass;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstructorAssignmentAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function instructor(): InstructorData
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        return InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Available Instructor',
            'available_for_class' => true,
            'status' => true,
        ]);
    }

    public function test_it_accepts_an_instructor_who_covers_the_class_schedule(): void
    {
        $instructor = $this->instructor();
        $term = Term::create(['term_name' => 'Monday']);
        $time = Time::create(['term_id' => $term->id, 'time_name' => '09:00 AM - 10:30 AM']);

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

        $reason = app(InstructorAssignmentAvailability::class)->unavailableReason($instructor->user_id, $term->id, $time->id);

        $this->assertNull($reason);
    }

    public function test_it_rejects_an_instructor_with_a_manual_block(): void
    {
        $instructor = $this->instructor();
        $term = Term::create(['term_name' => 'Monday']);
        $time = Time::create(['term_id' => $term->id, 'time_name' => '09:00 AM - 10:30 AM']);

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
        InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'status' => InstructorScheduleBlock::STATUS_ACTIVE,
        ]);

        $reason = app(InstructorAssignmentAvailability::class)->unavailableReason($instructor->user_id, $term->id, $time->id);

        $this->assertSame('The selected instructor has blocked this class schedule.', $reason);
    }

    public function test_creating_a_class_rejects_an_instructor_outside_their_working_hours(): void
    {
        $instructor = $this->instructor();
        $term = Term::create(['term_name' => 'Monday']);
        $time = Time::create(['term_id' => $term->id, 'time_name' => '09:00 AM - 10:30 AM']);
        $course = Course::create([
            'title' => 'Availability Test Course',
            'slug' => 'availability-test-course',
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(CreateStudyClass::class)->handle([
            'title' => $course->title,
            'course_id' => $course->id,
            'teacher_id' => $instructor->user_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 12,
            'price' => 0,
        ]);
    }
}
