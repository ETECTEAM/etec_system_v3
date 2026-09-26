<?php

namespace Tests\Feature\Certificate;

use App\Http\Middleware\EnsureInstructorOnboardingComplete;
use App\Models\ClassCertificateRequest;
use App\Models\Student;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class InstructorCertificateRequestUpdateTest extends TestCase
{
    use CreatesAttendanceFixtures;
    use CreatesDashboardUsers;
    use RefreshDatabase;

    private User $instructorUser;

    private StudyClass $class;

    /** @var array<int, Student> */
    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class, EnsureInstructorOnboardingComplete::class]);
        $this->seedRoles();

        $this->instructorUser = $this->instructor();
        $this->class = $this->makeStudyClass([
            'teacher' => $this->instructorUser,
            'classType' => $this->makeClassType('Scholarship Class'),
        ]);

        foreach (range(1, 4) as $number) {
            $student = $this->makeStudent();
            $this->enroll($this->class, $student);
            $this->students[$number] = $student;
        }
    }

    private function ids(int ...$numbers): array
    {
        return array_map(fn (int $number): int => $this->students[$number]->id, $numbers);
    }

    private function submit(array $studentIds)
    {
        return $this->actingAs($this->instructorUser)->post(
            route('instructor.classes.certificate-request.store', $this->class->id),
            ['confirm_request' => true, 'student_ids' => $studentIds],
        );
    }

    private function markPrinted(int ...$numbers): void
    {
        foreach ($numbers as $number) {
            DB::table('student_certificate_normal')->insert([
                'student_id' => $this->students[$number]->id,
                'study_class_id' => $this->class->id,
                'certificate_type' => 'scholarship',
                'student_name' => $this->students[$number]->full_name,
                'course' => 'Test Course',
                'granted_date' => 'September 26, 2026',
                'certificate_id' => '2609'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).' ETEC',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function request(): ClassCertificateRequest
    {
        return ClassCertificateRequest::query()->where('study_class_id', $this->class->id)->firstOrFail();
    }

    public function test_first_request_is_created_as_pending(): void
    {
        $this->submit($this->ids(1, 2))->assertRedirect()->assertSessionHas('success');

        $request = $this->request();
        $this->assertSame('pending', $request->status);
        $this->assertSame(2, $request->student_count);
        $this->assertSame('scholarship', $request->certificate_type);
    }

    public function test_instructor_can_add_a_forgotten_student_to_a_pending_request(): void
    {
        $this->submit($this->ids(1, 2));
        $first = $this->request();

        $this->travel(2)->days();
        $this->submit($this->ids(1, 2, 3))->assertSessionHas('success', 'Certificate request updated successfully.');

        $updated = $this->request();
        $this->assertSame($first->id, $updated->id);
        $this->assertSame(3, $updated->student_count);
        $this->assertEqualsCanonicalizing($this->ids(1, 2, 3), $updated->requested_student_ids);
        // The original request date and requester are kept so the admin's month filter does not move.
        $this->assertTrue($first->requested_at->equalTo($updated->requested_at));
        $this->assertSame($first->requested_by, $updated->requested_by);
    }

    public function test_printed_students_cannot_be_removed_from_the_request(): void
    {
        $this->submit($this->ids(1, 2));
        $this->markPrinted(1);

        $this->submit($this->ids(2, 3))->assertSessionHasErrors('student_ids');

        $this->assertEqualsCanonicalizing($this->ids(1, 2), $this->request()->requested_student_ids);
    }

    public function test_an_unprinted_student_can_be_taken_out_again(): void
    {
        $this->submit($this->ids(1, 2));
        $this->markPrinted(1);

        $this->submit($this->ids(1, 3))->assertSessionHas('success');

        $this->assertEqualsCanonicalizing($this->ids(1, 3), $this->request()->requested_student_ids);
    }

    public function test_request_cannot_change_once_every_student_has_a_certificate(): void
    {
        $this->submit($this->ids(1, 2, 3, 4));
        $this->markPrinted(1, 2, 3, 4);

        $this->submit($this->ids(1, 2, 3))->assertSessionHas('warning');

        $this->assertCount(4, $this->request()->requested_student_ids);
    }

    public function test_submitting_the_same_students_changes_nothing(): void
    {
        $this->submit($this->ids(1, 2));

        $this->submit($this->ids(2, 1))->assertSessionHas('warning', 'Nothing changed in the certificate request.');
    }

    public function test_request_page_reports_printed_students_and_whether_it_can_be_updated(): void
    {
        $this->submit($this->ids(1, 2));
        $this->markPrinted(1);

        $this->actingAs($this->instructorUser)
            ->get(route('instructor.classes.certificate-request', $this->class->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('certificateRequest.can_update', true)
                ->where('certificateRequest.printed_student_ids', $this->ids(1)));

        $this->markPrinted(2, 3, 4);

        $this->actingAs($this->instructorUser)
            ->get(route('instructor.classes.certificate-request', $this->class->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('certificateRequest.can_update', false));
    }

    public function test_students_outside_the_class_are_still_rejected(): void
    {
        $outsider = $this->makeStudent();

        $this->submit([$outsider->id])->assertSessionHasErrors('student_ids');

        $this->assertDatabaseMissing('class_certificate_requests', ['study_class_id' => $this->class->id]);
    }
}
