<?php

namespace Tests\Unit\Enroll;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Actions\CreateStudyClass;
use App\Modules\Enroll\Actions\UpdateStudyClass;
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

    public function test_updating_own_class_skips_the_instructor_availability_recheck(): void
    {
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('instructor');

        $classType = ClassType::create(['type_name' => 'Scholarship Class', 'is_active' => true]);
        $course = Course::create([
            'title' => 'Availability Update Course',
            'slug' => 'availability-update-course',
            'status' => 'active',
        ]);
        $currentTerm = Term::create(['term_name' => 'Friday']);
        $currentTime = Time::create(['term_id' => $currentTerm->id, 'time_name' => '10:00 AM - 12:30 PM']);
        $newTerm = Term::create(['term_name' => 'Monday']);
        $newTime = Time::create(['term_id' => $newTerm->id, 'time_name' => '09:00 AM - 10:30 AM']);

        $class = StudyClass::create([
            'title' => 'Existing Class',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'class_type_id' => $classType->class_type_id,
            'term_id' => $currentTerm->id,
            'time_id' => $currentTime->id,
            'status' => 'active',
            'capacity' => 12,
            'price' => 0,
        ]);

        $updated = app(UpdateStudyClass::class)->handle($class, [
            'title' => 'Existing Class',
            'course_id' => $course->id,
            'lesson_id' => null,
            'teacher_id' => $teacher->id,
            'room_id' => null,
            'class_type_id' => $classType->class_type_id,
            'term_id' => $newTerm->id,
            'time_id' => $newTime->id,
            'status' => 'active',
            'capacity' => 12,
            'price' => 0,
            'document_price' => 0,
            'enrollment_start_date' => null,
            'start_date' => null,
            'end_date' => null,
        ]);

        $this->assertSame($class->id, $updated->id);
        $this->assertSame($newTerm->id, $updated->fresh()->term_id);
        $this->assertSame($newTime->id, $updated->fresh()->time_id);
    }
}
