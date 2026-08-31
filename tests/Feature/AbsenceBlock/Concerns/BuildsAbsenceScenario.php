<?php

namespace Tests\Feature\AbsenceBlock\Concerns;

use App\Models\AttendanceRule;
use App\Models\AttendanceRuleSetting;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

/**
 * Fixture helpers for the absence-block feature: weekday vs weekend classes,
 * phone-controlled students, and quick attendance rows.
 */
trait BuildsAbsenceScenario
{
    use CreatesAttendanceFixtures;

    protected function setUpBuildsAbsenceScenario(): void
    {
        Cache::forget(AttendanceRuleSetting::CACHE_KEY);
    }

    protected function makeCourseNamed(string $title = 'Course'): Course
    {
        return Course::query()->create(['title' => $title, 'slug' => Str::slug($title).'-'.Str::random(6)]);
    }

    protected function weekdayClass(?Course $course = null): StudyClass
    {
        return $this->makeStudyClass([
            'course' => $course ?? $this->makeCourseNamed('Weekday'),
            'term' => $this->makeTerm('Mon & Tue'),
        ]);
    }

    protected function weekendClass(?Course $course = null): StudyClass
    {
        return $this->makeStudyClass([
            'course' => $course ?? $this->makeCourseNamed('Weekend'),
            'term' => $this->makeTerm('Saturday'),
        ]);
    }

    protected function studentWithPhone(string $phone): Student
    {
        return Student::query()->create([
            'full_name' => 'Student '.Str::random(5),
            'gender' => 'male',
            'phone' => $phone,
        ]);
    }

    protected function activeAbsenceRule(array $overrides = []): AttendanceRule
    {
        return AttendanceRule::query()->create(array_merge([
            'rule_type' => AttendanceRule::TYPE_ABSENCE,
            'limit_count' => 3,
            'period_type' => AttendanceRule::PERIOD_BOTH,
            'start_date' => '2026-04-01',
            'is_active' => true,
        ], $overrides));
    }

    /** Record one attendance row (creating the enrollment if needed). */
    protected function record(StudyClass $class, Student $student, string $date, string $status = 'absent'): StudentAttendance
    {
        $enrollment = StudentEnrollment::query()->firstOrCreate(
            ['study_class_id' => $class->id, 'student_id' => $student->id],
            ['enrollment_status' => 'active', 'payment_status' => 'unpaid', 'fee_amount' => 0],
        );

        return StudentAttendance::query()->create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => $date,
            'status' => $status,
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
    }
}
