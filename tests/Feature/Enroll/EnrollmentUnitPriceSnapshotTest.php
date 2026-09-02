<?php

namespace Tests\Feature\Enroll;

use App\Models\CourseEnrollConfig;
use App\Modules\Enroll\Actions\EnrollStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

/**
 * The Enroll Config keeps both prices: course_price is charged, unit_price is
 * the list reference. Enrolling snapshots both onto the enrollment so a receipt
 * reprinted later still shows the $120 -> $49 breakdown.
 */
class EnrollmentUnitPriceSnapshotTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
    }

    public function test_enrolling_snapshots_unit_price_and_charges_the_course_price(): void
    {
        $class = $this->makeStudyClass();
        $student = $this->makeStudent();

        CourseEnrollConfig::create([
            'course_id' => $class->course_id,
            'time_id' => $class->time_id,
            'status' => 'open',
            'unit_price' => 120,
            'course_price' => 49,
            'document_price' => 5,
        ]);

        app(EnrollStudent::class)->handle($class, $student->id);

        $this->assertDatabaseHas('student_enrollments', [
            'study_class_id' => $class->id,
            'student_id' => $student->id,
            'fee_amount' => 49.00,
            'unit_price' => 120.00,
            'document_fee_amount' => 5.00,
        ]);
    }

    public function test_unit_price_falls_back_to_the_charged_fee_when_config_has_no_unit_price(): void
    {
        $class = $this->makeStudyClass();
        $student = $this->makeStudent();

        CourseEnrollConfig::create([
            'course_id' => $class->course_id,
            'time_id' => $class->time_id,
            'status' => 'open',
            'unit_price' => 0,
            'course_price' => 60,
            'document_price' => 5,
        ]);

        app(EnrollStudent::class)->handle($class, $student->id);

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'fee_amount' => 60.00,
            'unit_price' => 60.00,
        ]);
    }
}
