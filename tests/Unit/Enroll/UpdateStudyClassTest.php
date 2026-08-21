<?php

namespace Tests\Unit\Enroll;

use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Actions\UpdateStudyClass;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStudyClassTest extends TestCase
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
            'full_name' => 'Owner '.$user->id,
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

    public function test_updating_a_shared_class_syncs_the_owner_pivot_term(): void
    {
        $owner = $this->instructor();

        foreach ([1, 2, 4] as $day) {
            $this->availability($owner, $day);
        }

        $monThu = Term::create(['term_name' => 'Mon & Thu']);
        $monTue = Term::create(['term_name' => 'Mon & Tue']);
        $time = Time::create(['term_id' => $monThu->id, 'time_name' => '09:00 AM - 10:30 AM']);

        $course = Course::create([
            'title' => 'Basic IT',
            'slug' => 'basic-it-'.uniqid(),
            'status' => 'active',
        ]);

        $studyClass = StudyClass::create([
            'title' => $course->title,
            'course_id' => $course->id,
            'teacher_id' => $owner->user_id,
            'term_id' => $monThu->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 12,
            'price' => 100,
            'document_price' => 5,
            'enrollment_start_date' => now()->toDateString(),
        ]);

        // Owner's collapsed half was set to Mon & Thu; the class now moves to Mon & Tue.
        $studyClass->instructors()->attach($owner->user_id, [
            'term_id' => $monThu->id,
            'time_id' => $time->id,
        ]);

        app(UpdateStudyClass::class)->handle($studyClass, [
            'course_id' => $course->id,
            'teacher_id' => $owner->user_id,
            'term_id' => $monTue->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 12,
            'price' => 100,
            'document_price' => 5,
        ]);

        $this->assertDatabaseHas('study_class_instructors', [
            'study_class_id' => $studyClass->id,
            'user_id' => $owner->user_id,
            'term_id' => $monTue->id,
            'time_id' => $time->id,
        ]);
    }
}