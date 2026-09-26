<?php

namespace Tests\Feature\Certificate;

use App\Models\ClassCertificateRequest;
use App\Models\StudyClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class CertificateClassListTest extends TestCase
{
    use CreatesAttendanceFixtures;
    use CreatesDashboardUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    private function scholarshipClass(string $title, int $students, int $printed): StudyClass
    {
        $class = $this->makeStudyClass([
            'title' => $title,
            'classType' => $this->makeClassType('Scholarship Class'),
        ]);

        $enrolled = collect(range(1, $students))->map(function () use ($class) {
            $student = $this->makeStudent();
            $this->enroll($class, $student);

            return $student;
        });

        ClassCertificateRequest::query()->create([
            'study_class_id' => $class->id,
            'certificate_type' => 'scholarship',
            'status' => 'pending',
            'student_count' => $students,
            'requested_student_ids' => $enrolled->pluck('id')->all(),
            'requested_at' => now(),
        ]);

        $enrolled->take($printed)->each(fn ($student) => DB::table('student_certificate_normal')->insert([
            'student_id' => $student->id,
            'study_class_id' => $class->id,
            'certificate_type' => 'scholarship',
            'student_name' => $student->full_name,
            'course' => 'Test Course',
            'granted_date' => 'January 15, 2026',
            'certificate_id' => '2601'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT).' ETEC',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return $class;
    }

    private function listClasses(array $query = []): array
    {
        $response = $this->actingAs($this->admin())
            ->getJson(route('dashboard.certificates.classes', ['type' => 'scholarship', 'year' => now()->year] + $query))
            ->assertOk();

        return $response->json('data');
    }

    public function test_fully_printed_class_stays_in_the_list_marked_as_printed(): void
    {
        $done = $this->scholarshipClass('Done class', students: 2, printed: 2);
        $partial = $this->scholarshipClass('Partial class', students: 3, printed: 1);

        $rows = collect($this->listClasses())->keyBy('id');

        $this->assertCount(2, $rows);
        $this->assertSame('printed', $rows[$done->id]['print_status']);
        $this->assertSame(0, $rows[$done->id]['remaining_students']);
        $this->assertSame('not_printed', $rows[$partial->id]['print_status']);
        $this->assertSame(2, $rows[$partial->id]['remaining_students']);
        $this->assertNotSame('-', $rows[$done->id]['requested_at']);
    }

    public function test_unprinted_classes_are_listed_before_printed_ones(): void
    {
        // The printed class is created last so it would come first by id order.
        $pending = $this->scholarshipClass('Pending class', students: 2, printed: 0);
        $done = $this->scholarshipClass('Done class', students: 2, printed: 2);

        $ids = collect($this->listClasses())->pluck('id')->all();

        $this->assertSame([$pending->id, $done->id], $ids);
    }

    public function test_status_filter_narrows_the_list(): void
    {
        $done = $this->scholarshipClass('Done class', students: 1, printed: 1);
        $pending = $this->scholarshipClass('Pending class', students: 1, printed: 0);

        $this->assertSame([$pending->id], collect($this->listClasses(['status' => 'not_printed']))->pluck('id')->all());
        $this->assertSame([$done->id], collect($this->listClasses(['status' => 'printed']))->pluck('id')->all());
        $this->assertCount(2, $this->listClasses(['status' => 'all']));
        // An unknown status falls back to showing everything.
        $this->assertCount(2, $this->listClasses(['status' => 'bogus']));
    }

    public function test_report_and_list_agree_on_print_status(): void
    {
        $done = $this->scholarshipClass('Done class', students: 2, printed: 2);

        $report = $this->actingAs($this->admin())
            ->getJson(route('dashboard.certificates.report.classes', ['certificate_type' => 'scholarship', 'year' => now()->year]))
            ->assertOk()
            ->json('data');

        $listRow = collect($this->listClasses())->firstWhere('id', $done->id);
        $reportRow = collect($report)->firstWhere('id', $done->id);

        $this->assertSame($listRow['print_status'], $reportRow['print_status']);
        $this->assertSame($listRow['remaining_students'], $reportRow['remaining_students']);
    }
}
