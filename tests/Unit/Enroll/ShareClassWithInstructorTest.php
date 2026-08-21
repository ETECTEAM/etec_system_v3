<?php

namespace Tests\Unit\Enroll;

use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Actions\ShareClassWithInstructor;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShareClassWithInstructorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function instructor(string $name = 'Instructor'): InstructorData
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        return InstructorData::create([
            'user_id' => $user->id,
            'full_name' => $name.' '.$user->id,
            'available_for_class' => true,
            'status' => true,
        ]);
    }

    private function availability(InstructorData $instructor, int $dayOfWeek): void
    {
        InstructorAvailability::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => $dayOfWeek,
            'employment_type' => 'full_time',
            'shift_group' => 'custom',
            'period' => 'daytime',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);
    }

    private function openClass(int $ownerUserId, int $termId, int $timeId, int $capacity = 12): StudyClass
    {
        $course = Course::create([
            'title' => 'Basic IT',
            'slug' => 'basic-it-'.uniqid(),
            'status' => 'active',
        ]);

        return StudyClass::create([
            'title' => $course->title,
            'course_id' => $course->id,
            'teacher_id' => $ownerUserId,
            'term_id' => $termId,
            'time_id' => $timeId,
            'status' => 'upcoming',
            'capacity' => $capacity,
            'price' => 100,
            'document_price' => 5,
            'enrollment_start_date' => now()->toDateString(),
        ]);
    }

    public function test_it_accepts_a_valid_split_between_two_available_instructors(): void
    {
        $owner = $this->instructor();
        $partner = $this->instructor();

        foreach ([1, 2] as $day) {
            $this->availability($owner, $day);
            $this->availability($partner, $day + 2);
        }

        $monTue = Term::create(['term_name' => 'Mon & Tue']);
        $wedThu = Term::create(['term_name' => 'Wed & Thu']);
        $time = Time::create(['term_id' => $monTue->id, 'time_name' => '09:00 AM - 10:30 AM']);
        $studyClass = $this->openClass($owner->user_id, $monTue->id, $time->id);

        $class = app(ShareClassWithInstructor::class)->handle($studyClass, [
            'instructor_id' => $partner->user_id,
            'instructor_term_id' => $wedThu->id,
            'instructor_subject' => 'Network',
            'owner_term_id' => $monTue->id,
            'owner_subject' => 'Code',
        ]);

        $this->assertDatabaseHas('study_class_instructors', [
            'study_class_id' => $class->id,
            'user_id' => $partner->user_id,
            'term_id' => $wedThu->id,
            'time_id' => $time->id,
            'subject' => 'Network',
        ]);
    }

    public function test_it_rejects_a_partner_who_is_not_available_on_their_days(): void
    {
        $owner = $this->instructor();
        $partner = $this->instructor();

        foreach ([1, 2] as $day) {
            $this->availability($owner, $day);
        }

        $monTue = Term::create(['term_name' => 'Mon & Tue']);
        $wedThu = Term::create(['term_name' => 'Wed & Thu']);
        $time = Time::create(['term_id' => $monTue->id, 'time_name' => '09:00 AM - 10:30 AM']);
        $studyClass = $this->openClass($owner->user_id, $monTue->id, $time->id);

        $this->expectException(ValidationException::class);

        app(ShareClassWithInstructor::class)->handle($studyClass, [
            'instructor_id' => $partner->user_id,
            'instructor_term_id' => $wedThu->id,
            'owner_term_id' => $monTue->id,
        ]);
    }

    public function test_it_rejects_a_partner_who_already_teaches_on_those_days(): void
    {
        $owner = $this->instructor();
        $partner = $this->instructor();

        foreach ([1, 2, 3, 4] as $day) {
            $this->availability($owner, $day);
            $this->availability($partner, $day);
        }

        $monTue = Term::create(['term_name' => 'Mon & Tue']);
        $wedThu = Term::create(['term_name' => 'Wed & Thu']);
        $time = Time::create(['term_id' => $monTue->id, 'time_name' => '09:00 AM - 10:30 AM']);

        // Partner already teaches another class on Wed & Thu at this time.
        $this->openClass($partner->user_id, $wedThu->id, $time->id);

        $studyClass = $this->openClass($owner->user_id, $monTue->id, $time->id);

        $this->expectException(ValidationException::class);

        app(ShareClassWithInstructor::class)->handle($studyClass, [
            'instructor_id' => $partner->user_id,
            'instructor_term_id' => $wedThu->id,
            'owner_term_id' => $monTue->id,
        ]);
    }

    public function test_it_rejects_an_owner_who_is_not_available_on_their_new_days(): void
    {
        $owner = $this->instructor();
        $partner = $this->instructor();

        foreach ([3, 4] as $day) {
            $this->availability($partner, $day);
        }

        $monTue = Term::create(['term_name' => 'Mon & Tue']);
        $wedThu = Term::create(['term_name' => 'Wed & Thu']);
        $time = Time::create(['term_id' => $monTue->id, 'time_name' => '09:00 AM - 10:30 AM']);
        $studyClass = $this->openClass($owner->user_id, $monTue->id, $time->id);

        $this->expectException(ValidationException::class);

        app(ShareClassWithInstructor::class)->handle($studyClass, [
            'instructor_id' => $partner->user_id,
            'instructor_term_id' => $wedThu->id,
            'owner_term_id' => $monTue->id,
        ]);
    }
}