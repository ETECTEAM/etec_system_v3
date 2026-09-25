<?php

namespace App\Modules\Instructor\Services;

use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ImportInstructorAttendanceCsv
{
    public function __construct(
        private readonly StudentRegistrationService $registration,
        private readonly AutoBlockStudent $autoBlock,
    ) {}

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
        $result = ['students_created' => 0, 'enrollments_created' => 0, 'attendance_imported' => 0, 'rows_skipped' => 0, 'students_blocked' => 0, 'scores_imported' => 0];
        $latestAbsent = [];
        $scores = [];
        // Latest date already stored per enrollment, read before this file writes anything,
        // so an older export can never roll a newer score back.
        $lastStoredDate = DB::table('student_attendances')->where('study_class_id', $class->id)->groupBy('student_enrollment_id')->pluck(DB::raw('max(attendance_date)'), 'student_enrollment_id');

        DB::transaction(function () use ($handle, $index, $class, $trackedBy, $lastStoredDate, &$result, &$latestAbsent, &$scores): void {
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
                // Legacy students already paid in the old system; amount/paid_at stay empty so revenue reports don't count them.
                $enrollment = StudentEnrollment::query()->firstOrCreate(
                    ['study_class_id' => $class->id, 'student_id' => $student->id],
                    ['course_id' => $class->course_id, 'term_id' => $class->term_id, 'time_id' => $class->time_id, 'enrollment_status' => 'active', 'payment_status' => 'paid', 'source' => 'legacy_csv', 'fee_amount' => 0, 'document_fee_amount' => 0, 'amount_paid' => 0, 'enrolled_at' => $date]
                );
                if ($enrollment->wasRecentlyCreated) $result['enrollments_created']++;
                $status = ((int) ($row[$index['permission']] ?? 0) === 1) ? 'permission' : (((int) ($row[$index['absent']] ?? 0) === 1) ? 'absent' : (((int) ($row[$index['present']] ?? 0) === 1) ? 'present' : 'pending'));
                DB::table('student_attendances')->updateOrInsert(
                    ['study_class_id' => $class->id, 'student_enrollment_id' => $enrollment->id, 'attendance_date' => $date],
                    ['student_id' => $student->id, 'tracked_by' => $trackedBy, ...StudentAttendance::flagsFor($status), 'source' => StudentAttendance::SOURCE_MANUAL, 'note' => trim((string) ($row[$index['reason']] ?? '')) ?: 'Imported from legacy CSV', 'updated_at' => now(), 'created_at' => now()]
                );
                $result['attendance_imported']++;

                if ($status === 'absent' && $date > ($latestAbsent[$student->id] ?? '')) {
                    $latestAbsent[$student->id] = $date;
                }

                if ((isset($index['act score']) || isset($index['exam score'])) && $date >= ($scores[$enrollment->id]['date'] ?? '')) {
                    $scores[$enrollment->id] = [
                        'student_id' => $student->id,
                        'date' => $date,
                        'activity' => $this->score($row[$index['act score'] ?? -1] ?? null),
                        'exam' => $this->score($row[$index['exam score'] ?? -1] ?? null),
                    ];
                }
            }

            foreach ($scores as $enrollmentId => $score) {
                // Only the newest export wins: skip when the class already has attendance dated after this file's last row.
                if (($lastStoredDate[$enrollmentId] ?? '') > $score['date']) {
                    continue;
                }

                $existing = DB::table('student_scores')->where('student_enrollment_id', $enrollmentId)->first();

                if ($existing && (float) $existing->activity_score === $score['activity'] && (float) $existing->exam_score === $score['exam']) {
                    continue;
                }

                DB::table('student_scores')->updateOrInsert(
                    ['student_enrollment_id' => $enrollmentId],
                    ['study_class_id' => $class->id, 'student_id' => $score['student_id'], 'activity_score' => $score['activity'], 'exam_score' => $score['exam'], 'updated_at' => now(), 'created_at' => $existing->created_at ?? now()]
                );
                $result['scores_imported']++;
            }
        });
        fclose($handle);

        // Enrollments are inserted directly, so grow the class to fit like every other
        // enrolment path does (capacity is a floor, never a ceiling).
        $activeStudents = DB::table('student_enrollments')->where('study_class_id', $class->id)->where('enrollment_status', 'active')->count();

        if ($activeStudents > (int) $class->capacity) {
            DB::table('study_classes')->where('id', $class->id)->update(['capacity' => $activeStudents, 'updated_at' => now()]);
        }

        // Imported rows skip the instructor save path, so run the absence-limit check here
        // (once per student, on their latest absence) or they'd never be blocked.
        $actor = User::query()->find($trackedBy);

        foreach ($latestAbsent as $studentId => $date) {
            if ($this->autoBlock->handle((int) $studentId, (int) $class->id, $date, $actor)?->wasRecentlyCreated) {
                $result['students_blocked']++;
            }
        }

        return $result;
    }

    // Scores are 0-30 in this system; blank or non-numeric cells count as 0.
    private function score(mixed $value): float
    {
        return is_numeric($value) ? min(30.0, max(0.0, round((float) $value, 2))) : 0.0;
    }
}
