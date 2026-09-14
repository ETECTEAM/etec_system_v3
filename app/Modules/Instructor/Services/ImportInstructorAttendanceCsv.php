<?php

namespace App\Modules\Instructor\Services;

use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ImportInstructorAttendanceCsv
{
    public function __construct(private readonly StudentRegistrationService $registration) {}

    public function handle(\stdClass $class, UploadedFile $file, int $trackedBy): array
    {
        $class = DB::table('study_classes')->where('id', $class->id)->first();
        if (! $class) {
            throw ValidationException::withMessages(['file' => 'The selected class could not be found.']);
        }
        $handle = fopen($file->getRealPath(), 'rb');
        $header = false;
        while ($handle && ($candidate = fgetcsv($handle)) !== false) {
            if (count(array_filter($candidate, static fn ($value): bool => trim((string) $value) !== '')) > 0) {
                $header = $candidate;
                break;
            }
        }
        if (! $handle || ! is_array($header)) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be read.']);
        }
        $header = array_map(static function ($value): string {
            $value = (string) $value;
            if (strncmp($value, "\xEF\xBB\xBF", 3) === 0) {
                $value = substr($value, 3);
            }

            return strtolower(trim($value));
        }, $header);
        $required = ['name', 'gender', 'tel', 'date', 'present', 'absent', 'permission'];
        if (array_diff($required, $header)) {
            throw ValidationException::withMessages(['file' => 'CSV headers must include Name, Gender, Tel, Date, Present, Absent, and Permission.']);
        }
        $index = array_flip($header);
        $result = ['students_created' => 0, 'enrollments_created' => 0, 'attendance_imported' => 0, 'rows_skipped' => 0];

        DB::transaction(function () use ($handle, $index, $class, $trackedBy, &$result): void {
            while (($row = fgetcsv($handle)) !== false) {
                $name = trim((string) ($row[$index['name']] ?? ''));
                $phone = trim((string) ($row[$index['tel']] ?? ''));
                $date = trim((string) ($row[$index['date']] ?? ''));
                if ($name === '' || $phone === '' || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) || ! Carbon::createFromFormat('Y-m-d', $date)) {
                    $result['rows_skipped']++;
                    continue;
                }
                $student = DB::table('students')->where('phone', $phone)->first();
                if (! $student) {
                    $student = $this->registration->createStudent([
                        'name' => $name,
                        'gender' => strtolower(trim((string) ($row[$index['gender']] ?? ''))) ?: 'male',
                        'phone' => $phone,
                    ], null, ['course_id' => $class->course_id, 'term_id' => $class->term_id, 'time_id' => $class->time_id]);
                    $result['students_created']++;
                }
                $enrollment = StudentEnrollment::query()->firstOrCreate(
                    ['study_class_id' => $class->id, 'student_id' => $student->id],
                    ['course_id' => $class->course_id, 'term_id' => $class->term_id, 'time_id' => $class->time_id, 'enrollment_status' => 'active', 'payment_status' => 'unpaid', 'source' => 'legacy_csv', 'fee_amount' => 0, 'document_fee_amount' => 0, 'amount_paid' => 0, 'enrolled_at' => $date]
                );
                if ($enrollment->wasRecentlyCreated) $result['enrollments_created']++;
                $status = ((int) ($row[$index['permission']] ?? 0) === 1) ? 'permission' : (((int) ($row[$index['absent']] ?? 0) === 1) ? 'absent' : (((int) ($row[$index['present']] ?? 0) === 1) ? 'present' : 'pending'));
                DB::table('student_attendances')->updateOrInsert(
                    ['study_class_id' => $class->id, 'student_enrollment_id' => $enrollment->id, 'attendance_date' => $date],
                    ['student_id' => $student->id, 'tracked_by' => $trackedBy, ...StudentAttendance::flagsFor($status), 'source' => StudentAttendance::SOURCE_MANUAL, 'note' => trim((string) ($row[$index['reason']] ?? '')) ?: 'Imported from legacy CSV', 'updated_at' => now(), 'created_at' => now()]
                );
                $result['attendance_imported']++;
            }
        });
        fclose($handle);
        return $result;
    }
}
