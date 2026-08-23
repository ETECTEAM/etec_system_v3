<?php

namespace Tests\Unit\Enroll;

use App\Models\Course;
use App\Models\PendingRegistration;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Enroll\Actions\AssignPendingStudentToClass;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignPendingStudentToClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Event::fake();
    }

    private function term(string $name = 'Monday'): Term
    {
        return Term::create(['term_name' => $name]);
    }

    private function time(Term $term, string $name): Time
    {
        return Time::create(['term_id' => $term->id, 'time_name' => $name]);
    }

    private function course(): Course
    {
        return Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function studyClass(Course $course, Term $term, Time $time, array $overrides = []): StudyClass
    {
        return StudyClass::create(array_merge([
            'title' => $course->title,
            'course_id' => $course->id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 1,
            'price' => 100,
            'document_price' => 5,
            'enrollment_start_date' => now()->toDateString(),
        ], $overrides));
    }

    // Mirrors RegisterStudentForScheduleTest::parkRegistration - no rooms or
    // instructors exist, so a registration against a seatless class ends up
    // parked instead of enrolled.
    private function pendingRegistration(StudyClass $studyClass): PendingRegistration
    {
        return PendingRegistration::create([
            'student_id' => \App\Models\Student::create([
                'full_name' => 'Parked Student',
                'gender' => 'male',
                'phone' => '0123'.random_int(100000, 999999),
                'student_status' => 'active',
            ])->id,
            'course_id' => $studyClass->course_id,
            'term_id' => $studyClass->term_id,
            'time_id' => $studyClass->time_id,
            'status' => 'pending',
        ]);
    }

    public function test_assigns_into_a_full_class_and_expands_capacity_by_default(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $studyClass = $this->studyClass($course, $term, $time);

        // Fill the only seat so the assignment genuinely overbooks.
        StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => \App\Models\Student::create([
                'full_name' => 'Seat Filler',
                'gender' => 'female',
                'phone' => '0987654321',
                'student_status' => 'active',
            ])->id,
            'fee_amount' => 100,
            'document_fee_amount' => 5,
        ]);

        $pending = $this->pendingRegistration($studyClass);

        $enrollment = app(AssignPendingStudentToClass::class)->handle($pending->id, $studyClass->id);

        $this->assertSame($studyClass->id, $enrollment->study_class_id);
        $this->assertSame($pending->student_id, $enrollment->student_id);
        $this->assertSame('admin_force_assignment', $enrollment->source);
        $this->assertSame(2, $studyClass->fresh()->capacity);
        $this->assertSame('resolved', $pending->fresh()->status);
    }

    public function test_rejects_a_full_class_when_override_capacity_is_false(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();

        $fillerStudent = \App\Models\Student::create([
            'full_name' => 'Seat Filler',
            'gender' => 'female',
            'phone' => '0987654321',
            'student_status' => 'active',
        ]);
        $studyClass = $this->studyClass($course, $term, $time);
        StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $fillerStudent->id,
            'fee_amount' => 100,
            'document_fee_amount' => 5,
        ]);

        $pending = $this->pendingRegistration($studyClass);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This class is full.');

        app(AssignPendingStudentToClass::class)->handle($pending->id, $studyClass->id, overrideCapacity: false);
    }

    public function test_rejects_a_class_that_fails_the_two_week_rule(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $studyClass = $this->studyClass($course, $term, $time, ['capacity' => 10]);
        DB::table('study_classes')->where('id', $studyClass->id)->update([
            'created_at' => now()->subWeeks(3)->toDateTimeString(),
        ]);

        $pending = $this->pendingRegistration($studyClass);

        try {
            app(AssignPendingStudentToClass::class)->handle($pending->id, $studyClass->id);
            $this->fail('Expected the two-week rule to reject this class.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('study_class_id', $exception->errors());
        }

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertDatabaseMissing('student_enrollments', ['study_class_id' => $studyClass->id]);
    }

    public function test_accepts_an_older_class_whose_start_date_is_within_two_weeks(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $studyClass = $this->studyClass($course, $term, $time, [
            'capacity' => 10,
            'start_date' => now()->addWeeks(1)->toDateString(),
        ]);
        DB::table('study_classes')->where('id', $studyClass->id)->update([
            'created_at' => now()->subWeeks(3)->toDateTimeString(),
        ]);

        $pending = $this->pendingRegistration($studyClass);

        $enrollment = app(AssignPendingStudentToClass::class)->handle($pending->id, $studyClass->id, overrideCapacity: false);

        $this->assertSame('resolved', $pending->fresh()->status);
        $this->assertSame(10, $studyClass->fresh()->capacity, 'Capacity must not change without the override.');
    }

    public function test_cannot_resolve_the_same_pending_registration_twice(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $firstClass = $this->studyClass($course, $term, $time);
        $secondClass = $this->studyClass($course, $term, $this->time($term, '10:30 AM - 12:00 PM'));
        $pending = $this->pendingRegistration($firstClass);

        app(AssignPendingStudentToClass::class)->handle($pending->id, $firstClass->id);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already been resolved');

        app(AssignPendingStudentToClass::class)->handle($pending->id, $secondClass->id);
    }
}
