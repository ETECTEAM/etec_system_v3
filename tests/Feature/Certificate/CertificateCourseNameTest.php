<?php

namespace Tests\Feature\Certificate;

use App\Models\ClassCertificateRequest;
use App\Models\Course;
use App\Models\StudyClass;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class CertificateCourseNameTest extends TestCase
{
    use CreatesAttendanceFixtures;
    use CreatesDashboardUsers;
    use RefreshDatabase;

    private const TITLE = 'Java + Spring Boot (Basic/Advance - Java required)';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function scholarshipClass(): StudyClass
    {
        $course = Course::query()->create(['title' => self::TITLE, 'slug' => 'java-'.uniqid()]);
        $class = $this->makeStudyClass([
            'course' => $course,
            'classType' => $this->makeClassType('Scholarship Class'),
        ]);
        $student = $this->makeStudent();
        $this->enroll($class, $student);

        ClassCertificateRequest::query()->create([
            'study_class_id' => $class->id,
            'certificate_type' => 'scholarship',
            'status' => 'pending',
            'student_count' => 1,
            'requested_student_ids' => [$student->id],
            'requested_at' => now(),
        ]);

        return $class;
    }

    private function listedClass(StudyClass $class): array
    {
        return collect($this->getJson(route('dashboard.certificates.classes', [
            'type' => 'scholarship',
            'year' => now()->year,
        ]))->assertOk()->json('data'))->firstWhere('id', $class->id);
    }

    public function test_admin_saves_a_certificate_only_name_without_touching_the_title(): void
    {
        $class = $this->scholarshipClass();
        $this->actingAs($this->admin());

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
            'course_name' => 'Java + Spring Boot',
        ])
            ->assertOk()
            ->assertJsonPath('certificate_course', 'Java + Spring Boot')
            ->assertJsonPath('is_custom', true)
            ->assertJsonPath('names.0.course_name', 'Java + Spring Boot');

        $course = Course::query()->findOrFail($class->course_id);
        $this->assertSame('Java + Spring Boot', $course->course_cert_custom_name);
        $this->assertSame(self::TITLE, $course->title);

        $row = $this->listedClass($class);
        $this->assertSame(self::TITLE, $row['course']);
        $this->assertSame('Java + Spring Boot', $row['certificate_course']);
        $this->assertTrue($row['has_certificate_name']);
    }

    public function test_class_uses_the_course_title_when_no_certificate_name_is_set(): void
    {
        $class = $this->scholarshipClass();
        $this->actingAs($this->admin());

        $row = $this->listedClass($class);

        $this->assertSame(self::TITLE, $row['certificate_course']);
        $this->assertFalse($row['has_certificate_name']);
    }

    public function test_saving_the_same_wording_as_the_title_stores_nothing(): void
    {
        $class = $this->scholarshipClass();
        DB::table('courses')->where('id', $class->course_id)->update(['course_cert_custom_name' => 'Old wording']);
        $this->actingAs($this->admin());

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
            'course_name' => self::TITLE,
        ])->assertOk()->assertJsonPath('is_custom', false);

        $this->assertNull(Course::query()->findOrFail($class->course_id)->course_cert_custom_name);
    }

    public function test_reset_clears_the_certificate_name(): void
    {
        $class = $this->scholarshipClass();
        DB::table('courses')->where('id', $class->course_id)->update(['course_cert_custom_name' => 'Java + Spring Boot']);
        $this->actingAs($this->superAdmin());

        $this->deleteJson(route('dashboard.certificates.courses.destroy'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
        ])
            ->assertOk()
            ->assertJsonPath('certificate_course', self::TITLE)
            ->assertJsonPath('names', []);

        $this->assertNull(Course::query()->findOrFail($class->course_id)->course_cert_custom_name);
    }

    public function test_instructor_cannot_change_certificate_names(): void
    {
        $class = $this->scholarshipClass();
        $this->actingAs($this->instructor());

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
            'course_name' => 'Hacked wording',
        ])->assertForbidden();

        $this->deleteJson(route('dashboard.certificates.courses.destroy'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
        ])->assertForbidden();

        $this->assertNull(Course::query()->findOrFail($class->course_id)->course_cert_custom_name);
    }

    public function test_save_needs_a_course_and_a_reasonable_length(): void
    {
        $class = $this->scholarshipClass();
        $this->actingAs($this->admin());

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'normal',
            'course_name' => 'No course given',
        ])->assertUnprocessable()->assertJsonValidationErrors('course_id');

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'normal',
            'course_id' => $class->course_id,
            'course_name' => str_repeat('A', 101),
        ])->assertUnprocessable()->assertJsonValidationErrors('course_name');

        $this->assertNull(Course::query()->findOrFail($class->course_id)->course_cert_custom_name);
    }

    public function test_free_certificate_course_shortcuts_still_work_for_instructors(): void
    {
        $this->actingAs($this->instructor());

        $this->postJson(route('dashboard.certificates.courses.store'), [
            'scope' => 'free',
            'course_name' => 'Free Excel Basics',
        ])->assertOk();

        $this->assertDatabaseHas('course_custom', ['course_name' => 'Free Excel Basics']);
    }

    public function test_page_tells_the_ui_who_may_edit_certificate_names(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.certificates.index', ['type' => 'scholarship']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canEditCourseNames', true));

        $this->actingAs($this->instructor())
            ->get(route('dashboard.certificates.index', ['type' => 'scholarship']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canEditCourseNames', false));
    }
}
