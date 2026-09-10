<?php

namespace Tests\Feature\Enroll;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

/**
 * "Enrollment Management -> Manual Register" — record an off-system/paid-in-person
 * registration by hand, then let an instructor pull that same paid record into a
 * real class later. See docs/manual-register-plan.md.
 *
 *  - Story A: POST /dashboard/enroll/manual-registrations (admin only)
 *  - Story B: instructor "Add Existing Student" — assignable-registrations + assign-registration
 *  - Story C: admin "Assign to Class" from the Registrations tab — registrations/{enrollment}/move
 */
class ManualRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    // ---------------------------------------------------------------------
    // Fixtures
    // ---------------------------------------------------------------------

    private function createCourse(string $title = 'Adobe Photoshop'): Course
    {
        return Course::firstOrCreate(
            ['slug' => str($title)->slug()->toString()],
            ['title' => $title, 'status' => 'active'],
        );
    }

    private function createClassType(): ClassType
    {
        return ClassType::firstOrCreate(['type_name' => 'Network'], ['is_active' => true]);
    }

    private function createScheduleGrid(ClassType $classType, string $timeName = '09:00 AM - 10:30 AM'): array
    {
        $term = Term::firstOrCreate(['term_name' => 'Mon & Thu']);
        $time = Time::firstOrCreate(['time_name' => $timeName]);

        $schedule = Schedule::firstOrCreate([
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
        ]);
        $schedule->times()->syncWithoutDetaching([$time->id]);

        return compact('term', 'time');
    }

    /** A second, distinct time slot for "same course, different section" cases. */
    private function altTime(): Time
    {
        return $this->createScheduleGrid($this->createClassType(), '11:00 AM - 12:30 PM')['time'];
    }

    private function createStudyClass(array $attributes = []): StudyClass
    {
        $classType = $this->createClassType();
        ['term' => $term, 'time' => $time] = $this->createScheduleGrid($classType);

        return StudyClass::create(array_merge([
            'title' => $this->createCourse()->title,
            'course_id' => $this->createCourse()->id,
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'active',
            'capacity' => 20,
            'price' => 100,
            'document_price' => 0,
        ], $attributes));
    }

    /** A class-less, already-paid "manual" registration — what Story A leaves behind. */
    private function createManualRegistration(array $studentAttributes = [], array $enrollmentAttributes = []): StudentEnrollment
    {
        $student = Student::create(array_merge([
            'full_name' => 'Dara Kim',
            'gender' => 'male',
            'phone' => '098765433',
        ], $studentAttributes));

        return StudentEnrollment::create(array_merge([
            'study_class_id' => null,
            'student_id' => $student->id,
            'course_id' => $this->createCourse()->id,
            'enrollment_status' => 'unassigned',
            'payment_status' => 'paid',
            'source' => 'manual',
            'fee_amount' => 73,
            'document_fee_amount' => 5,
            'amount_paid' => 73,
        ], $enrollmentAttributes));
    }

    private function validManualPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Dara Kim',
            'gender' => 'male',
            'phone' => '098 765 433',
            'course' => 'Adobe Photoshop',
            'term' => 'Mon & Thu',
            'time' => '09:00 AM - 10:30 AM',
            'amount_paid' => 73,
            'discount' => 0,
            'document_price' => 5,
            'payment_method' => 'Cash',
            'payment_date' => '2026-01-15',
            'note' => 'Paid in person last month',
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // Story A — record an off-system registration
    // ---------------------------------------------------------------------

    public function test_admin_records_a_manual_registration_as_a_classless_paid_enrollment(): void
    {
        $course = $this->createCourse('Adobe Photoshop');
        $term = Term::firstOrCreate(['term_name' => 'Mon & Thu']);

        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload())
            ->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'enrollment_id']);

        $this->assertDatabaseHas('students', [
            'full_name' => 'Dara Kim',
            'gender' => 'male',
            'phone' => '098 765 433',
        ]);

        $this->assertDatabaseHas('student_enrollments', [
            'study_class_id' => null,
            'course_id' => $course->id,
            'term_id' => $term->id,
            'time_id' => null,
            'enrollment_status' => 'unassigned',
            'payment_status' => 'paid',
            'source' => 'manual',
            'fee_amount' => 73,
            'document_fee_amount' => 5,
            'amount_paid' => 73,
        ]);

        $enrollment = StudentEnrollment::latest('id')->first();
        $this->assertSame('2026-01-15', $enrollment->enrolled_at?->format('Y-m-d'));
        $this->assertNotNull($enrollment->paid_at);
    }

    public function test_manual_registration_leaves_course_and_term_null_when_names_do_not_match(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload([
                'course' => 'A Course That Does Not Exist',
                'term' => 'No Such Term',
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('student_enrollments', [
            'source' => 'manual',
            'course_id' => null,
            'term_id' => null,
        ]);
    }

    public function test_manual_registration_shows_in_the_registrations_tab_with_a_manual_marker(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload())
            ->assertCreated();

        $this->actingAs($this->admin())
            ->getJson('/dashboard/enroll/registrations/data?search=Dara')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Dara Kim')
            ->assertJsonPath('data.0.source', 'manual')
            ->assertJsonPath('data.0.registration_type', 'manual')
            ->assertJsonPath('data.0.payment_status', 'Paid')
            ->assertJsonPath('data.0.enrollment_status', 'Unassigned')
            ->assertJsonPath('data.0.class_id', null);
    }

    public function test_manual_registration_requires_the_core_fields(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll/manual-registrations', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'gender', 'phone', 'course', 'amount_paid']);
    }

    public function test_manual_registration_rejects_a_non_latin_full_name(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload([
                'full_name' => 'ដារ៉ា គីម',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_guest_cannot_record_a_manual_registration(): void
    {
        $this->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_instructor_cannot_record_a_manual_registration(): void
    {
        $this->actingAs($this->instructor())
            ->postJson('/dashboard/enroll/manual-registrations', $this->validManualPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('students', 0);
    }

    // ---------------------------------------------------------------------
    // Story B — instructor pulls the manual registration into their class
    // ---------------------------------------------------------------------

    public function test_instructor_sees_unassigned_manual_registrations_for_their_own_class(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);
        $enrollment = $this->createManualRegistration();

        $this->actingAs($instructor)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations")
            ->assertOk()
            ->assertJsonPath('data.0.enrollment_id', $enrollment->id)
            ->assertJsonPath('data.0.name', 'Dara Kim')
            ->assertJsonPath('data.0.registration_type', 'manual')
            ->assertJsonPath('data.0.payment_status', 'Paid')
            ->assertJsonPath('data.0.amount_paid', 73);
    }

    public function test_assignable_registrations_list_is_searchable_by_name(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);

        $this->createManualRegistration(['full_name' => 'Dara Kim', 'phone' => '098000001']);
        $this->createManualRegistration(['full_name' => 'Sok Chan', 'phone' => '098000002']);

        $this->actingAs($instructor)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations?search=Dara")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dara Kim');
    }

    public function test_instructor_assigns_a_manual_registration_into_their_class_in_place(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id, 'capacity' => 20]);
        $enrollment = $this->createManualRegistration();

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $enrollment->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $enrollment->fresh();
        $this->assertSame($studyClass->id, $fresh->study_class_id);
        $this->assertSame('active', $fresh->enrollment_status);
        $this->assertSame('manual', $fresh->source);
        $this->assertSame(73.0, (float) $fresh->amount_paid, 'payment must carry over untouched');

        // No duplicate student, no duplicate enrollment — the same row was updated.
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_enrollments', 1);
    }

    public function test_instructor_cannot_list_or_assign_registrations_for_another_instructors_class(): void
    {
        $owner = $this->instructor();
        $intruder = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $owner->id]);
        $enrollment = $this->createManualRegistration();

        $this->actingAs($intruder)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations")
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $enrollment->id,
            ])
            ->assertForbidden();

        $this->assertNull($enrollment->fresh()->study_class_id);
    }

    public function test_list_only_shows_registrations_for_this_classes_course(): void
    {
        $instructor = $this->instructor();
        $otherCourse = $this->createCourse('Graphic Design');
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);

        $mine = $this->createManualRegistration(['phone' => '098000010']); // course = default (Adobe Photoshop)
        $this->createManualRegistration(['full_name' => 'Off Course', 'phone' => '098000011'], [
            'course_id' => $otherCourse->id,
        ]);

        $this->actingAs($instructor)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.enrollment_id', $mine->id);
    }

    public function test_a_student_in_the_same_course_at_another_time_still_appears_and_can_be_added(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id, 'capacity' => 20]);
        $earlierSlot = $this->createStudyClass([
            'teacher_id' => $instructor->id,
            'title' => 'Earlier Slot',
            'time_id' => $this->altTime()->id,
        ]);

        $existing = $this->createManualRegistration([], [
            'study_class_id' => $earlierSlot->id,
            'enrollment_status' => 'active',
        ]);

        $this->actingAs($instructor)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations")
            ->assertOk()
            ->assertJsonPath('data.0.enrollment_id', $existing->id)
            ->assertJsonPath('data.0.current_class', 'Earlier Slot');

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $existing->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Original enrollment untouched; a fresh already-paid one sits in this class.
        $this->assertSame($earlierSlot->id, $existing->fresh()->study_class_id);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseHas('student_enrollments', [
            'study_class_id' => $studyClass->id,
            'student_id' => $existing->student_id,
            'enrollment_status' => 'active',
            'payment_status' => 'paid',
        ]);
        $this->assertSame(2, StudentEnrollment::where('student_id', $existing->student_id)->count());
    }

    public function test_assign_rejects_a_student_already_in_a_class_of_this_course_and_time(): void
    {
        $instructor = $this->instructor();
        $otherInstructor = $this->instructor();
        // Same course + same time, different instructor = a parallel section.
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);
        $parallel = $this->createStudyClass(['teacher_id' => $otherInstructor->id, 'title' => 'Parallel']);

        $existing = $this->createManualRegistration([], [
            'study_class_id' => $parallel->id,
            'enrollment_status' => 'active',
        ]);

        $this->actingAs($instructor)
            ->getJson("/dashboard/enroll/{$studyClass->id}/assignable-registrations")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $existing->id,
            ])
            ->assertStatus(422);

        $this->assertSame(1, StudentEnrollment::where('student_id', $existing->student_id)->count());
    }

    public function test_assign_rejects_a_student_already_in_this_class(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);

        $existing = $this->createManualRegistration([], [
            'study_class_id' => $studyClass->id,
            'enrollment_status' => 'active',
        ]);

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $existing->id,
            ])
            ->assertStatus(422);

        $this->assertSame(1, StudentEnrollment::where('student_id', $existing->student_id)->count());
    }

    public function test_assign_is_blocked_when_the_class_no_longer_accepts_changes(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id, 'status' => 'ended']);
        $enrollment = $this->createManualRegistration();

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $enrollment->id,
            ])
            ->assertStatus(422);

        $this->assertNull($enrollment->fresh()->study_class_id);
    }

    public function test_assign_is_rejected_when_the_class_is_full(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id, 'capacity' => 1]);

        // Fill the single seat.
        $seatTaker = Student::create(['full_name' => 'Seat Taker', 'gender' => 'female', 'phone' => '098111222']);
        StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $seatTaker->id,
            'enrollment_status' => 'active',
            'payment_status' => 'unpaid',
            'source' => 'admin_register',
            'fee_amount' => 100,
            'document_fee_amount' => 0,
            'amount_paid' => 0,
        ]);

        $enrollment = $this->createManualRegistration();

        $this->actingAs($instructor)
            ->postJson("/dashboard/enroll/{$studyClass->id}/assign-registration", [
                'enrollment_id' => $enrollment->id,
            ])
            ->assertStatus(422);

        $this->assertNull($enrollment->fresh()->study_class_id);
    }

    // ---------------------------------------------------------------------
    // Story C — admin assigns from the Registrations tab (equivalent path)
    // ---------------------------------------------------------------------

    public function test_admin_assigns_a_manual_registration_to_a_class_from_the_registrations_tab(): void
    {
        $studyClass = $this->createStudyClass();
        $enrollment = $this->createManualRegistration();

        $this->actingAs($this->admin())
            ->putJson("/dashboard/enroll/registrations/{$enrollment->id}/move", [
                'study_class_id' => $studyClass->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $enrollment->fresh();
        $this->assertSame($studyClass->id, $fresh->study_class_id);
        $this->assertSame('active', $fresh->enrollment_status);
        $this->assertSame(73.0, (float) $fresh->amount_paid);

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_enrollments', 1);
    }
}
