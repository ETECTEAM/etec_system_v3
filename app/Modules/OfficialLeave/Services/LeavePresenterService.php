<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\OfficialLeave;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read-side presenters for the official-leave admin screens: student search
 * cards (with block math), leave rows for history, and the review-card shape.
 */
class LeavePresenterService
{
    public function searchStudents(string $term, int $limit = 8): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $query = Student::query()
            ->whereHas('user', fn ($q) => $q->whereIn('status', ['active']));

        // A numeric term is treated as a student ID first, then falls back to a name match.
        if (ctype_digit($term)) {
            $query->where(fn ($q) => $q->whereKey((int) $term)->orWhere('full_name', 'like', "%{$term}%"));
        } else {
            $query->where('full_name', 'like', "%{$term}%");
        }

        return $query->orderBy('full_name')
            ->limit($limit)
            ->get()
            ->map(fn (Student $student) => $this->presentStudentCard($student))
            ->all();
    }

    public function presentStudentCard(Student $student): array
    {
        $student->loadMissing(['user.photo', 'course']);

        $enrollments = $student->enrollments()
            ->where('enrollment_status', 'active')
            ->with(['studyClass:id,title', 'course:id,title'])
            ->get();

        $classTitles = $enrollments
            ->map(fn ($enrollment) => $enrollment->studyClass?->title)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $courseTitle = $student->course?->title
            ?? $enrollments->first()?->course?->title;

        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'gender' => $student->gender,
            'photo_url' => $student->user?->photo?->url,
            'classes' => $classTitles,
            'course' => $courseTitle,
            'block' => $this->blockStatus($student->id),
        ];
    }

    /**
     * Block watchlist math from the school policy: real absences +
     * floor(permissions / permissions_per_absence), blocked once the sum reaches
     * absence_block_threshold.
     */
    public function blockStatus(int $studentId): array
    {
        $counts = DB::table('student_attendances')
            ->where('student_id', $studentId)
            ->selectRaw("sum(case when status = 'absent' then 1 else 0 end) as real_absences")
            ->selectRaw("sum(case when status = 'permission' then 1 else 0 end) as permissions")
            ->first();

        $realAbsences = (int) ($counts->real_absences ?? 0);
        $permissions = (int) ($counts->permissions ?? 0);
        $perAbsence = max(1, (int) official_leave_setting('permissions_per_absence'));
        $threshold = (int) official_leave_setting('absence_block_threshold');
        $converted = intdiv($permissions, $perAbsence);
        $score = $realAbsences + $converted;

        return [
            'real_absences' => $realAbsences,
            'permissions' => $permissions,
            'converted_absences' => $converted,
            'score' => $score,
            'threshold' => $threshold,
            'blocked' => $score >= $threshold,
        ];
    }

    /**
     * Filtered, paginated history rows. Filters: status, date_from/date_to (against
     * start_date), study_class_id, search (student name or ID), page.
     */
    public function historyQuery(array $filters): LengthAwarePaginator
    {
        return OfficialLeave::query()
            ->with([
                'student.user.photo',
                'studyClass:id,title,course_id',
                'studyClass.course:id,title',
                'approver:id,name',
                'rejecter:id,name',
                'revoker:id,name',
            ])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('start_date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('end_date', '<=', $to))
            ->when($filters['study_class_id'] ?? null, fn ($q, $classId) => $this->applyClassFilter($q, (int) $classId))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $term = trim($term);

                if ($term === '') {
                    return;
                }

                $q->whereHas('student', function ($sq) use ($term) {
                    if (ctype_digit($term)) {
                        $sq->whereKey((int) $term)->orWhere('full_name', 'like', "%{$term}%");
                    } else {
                        $sq->where('full_name', 'like', "%{$term}%");
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate(
                perPage: $filters['per_page'] ?? 10,
                page: max(1, (int) ($filters['page'] ?? 1)),
            );
    }

    private function applyClassFilter($query, int $classId): void
    {
        // A leave with a null class covers every class — it still matches when filtering
        // by a specific one; a scoped leave must be that exact class.
        $query->where(function ($q) use ($classId) {
            $q->whereNull('study_class_id')->orWhere('study_class_id', $classId);
        });
    }

    public function presentLeave(OfficialLeave $leave): array
    {
        $leave->loadMissing([
            'student.user.photo',
            'student.course:id,title',
            'student.enrollments.studyClass.course:id,title',
            'studyClass:id,title,course_id',
            'studyClass.course:id,title',
            'approver:id,name',
            'rejecter:id,name',
            'revoker:id,name',
        ]);

        $student = $leave->student;

        [$classTitles, $courseTitle] = $this->contextFor($leave, $student);

        return [
            'id' => $leave->id,
            'student' => [
                'id' => $student?->id,
                'full_name' => $student?->full_name ?? '(deleted student)',
                'photo_url' => $student?->user?->photo?->url,
            ],
            'classes' => $classTitles,
            'course' => $courseTitle,
            'start_date' => $leave->start_date?->toDateString(),
            'end_date' => $leave->end_date?->toDateString(),
            'days' => $leave->start_date && $leave->end_date ? $leave->start_date->diffInDays($leave->end_date) + 1 : null,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'rejection_note' => $leave->rejection_note,
            'revoked_note' => $leave->revoked_note,
            'requested_at' => $leave->created_at?->toIso8601String(),
            'approved_by' => $leave->approver?->name,
            'rejected_by' => $leave->rejecter?->name,
            'revoked_by' => $leave->revoker?->name,
            'deleted' => $leave->trashed(),
        ];
    }

    /**
     * Class/course display falls back to the student's active enrollments when the
     * leave itself isn't scoped to a single class.
     *
     * @return array{0: list<string>, 1: ?string}
     */
    private function contextFor(OfficialLeave $leave, ?Student $student): array
    {
        if ($leave->studyClass) {
            $classes = [$leave->studyClass->title];
            $course = $leave->studyClass->course?->title ?? $student?->course?->title;

            return [$classes, $course];
        }

        $enrollments = $student?->enrollments
            ->where('enrollment_status', 'active')
            ->values();

        $classes = $enrollments
            ->map(fn ($enrollment) => $enrollment->studyClass?->title)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $course = $student?->course?->title
            ?? $enrollments->first()?->studyClass?->course?->title;

        return [$classes, $course];
    }
}
