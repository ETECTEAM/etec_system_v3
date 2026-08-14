<?php

namespace Tests\Unit\Attendance\Concerns;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Builds the minimum valid fixtures the attendance auto-record feature needs:
 * an instructor, a course, a term/time pair, a class, and enrolled students.
 * Room/building/floor are skipped entirely — study_classes.room_id is nullable
 * and irrelevant to auto-record logic.
 */
trait CreatesAttendanceFixtures
{
    protected function makeInstructor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('instructor');

        return $user;
    }

    protected function makeTerm(string $name): Term
    {
        return Term::query()->firstOrCreate(['term_name' => $name]);
    }

    protected function makeTime(string $name): Time
    {
        return Time::query()->firstOrCreate(['time_name' => $name]);
    }

    protected function makeClassType(string $name = 'Physical Class'): ClassType
    {
        return ClassType::query()->firstOrCreate(['type_name' => $name]);
    }

    protected function makeCourse(): Course
    {
        $slug = 'test-course-'.Str::random(8);

        return Course::query()->create(['title' => 'Test Course', 'slug' => $slug]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeStudyClass(array $overrides = []): StudyClass
    {
        $term = $overrides['term'] ?? $this->makeTerm('Mon & Tue');
        $time = $overrides['time'] ?? $this->makeTime('09:00 AM - 10:30 AM');

        return StudyClass::query()->create([
            'title' => $overrides['title'] ?? 'Test Class',
            'course_id' => ($overrides['course'] ?? $this->makeCourse())->id,
            'teacher_id' => ($overrides['teacher'] ?? $this->makeInstructor())->id,
            'class_type_id' => ($overrides['classType'] ?? $this->makeClassType())->class_type_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => $overrides['status'] ?? 'active',
            'capacity' => $overrides['capacity'] ?? 20,
            'price' => 0,
        ]);
    }

    protected function makeStudent(): Student
    {
        return Student::query()->create([
            'full_name' => 'Test Student '.Str::random(6),
            'gender' => 'male',
            'phone' => '0'.random_int(10000000, 99999999),
        ]);
    }

    protected function enroll(StudyClass $class, Student $student): StudentEnrollment
    {
        return StudentEnrollment::query()->create([
            'study_class_id' => $class->id,
            'student_id' => $student->id,
            'enrollment_status' => 'active',
            'payment_status' => 'unpaid',
            'fee_amount' => 0,
        ]);
    }
}
