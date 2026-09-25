<?php

namespace App\Modules\Certificate\Actions;

use App\Models\ClassCertificateRequest;
use App\Models\StudentAttendanceBlock;
use App\Models\StudentEnrollment;

/**
 * Restores an unblocked student to an already-pending certificate request for
 * the class where the block was raised. The request itself remains pending and
 * keeps its original requester, note, and certificate type.
 */
class AddUnblockedStudentToPendingCertificateRequest
{
    public function handle(StudentAttendanceBlock $block): void
    {
        if (! $block->study_class_id || ! $block->student_id) {
            return;
        }

        $isActiveInBlockedClass = StudentEnrollment::query()
            ->where('study_class_id', $block->study_class_id)
            ->where('student_id', $block->student_id)
            ->where('enrollment_status', 'active')
            ->exists();

        if (! $isActiveInBlockedClass) {
            return;
        }

        $request = ClassCertificateRequest::query()
            ->where('study_class_id', $block->study_class_id)
            ->where('status', 'pending')
            // Only restore a student who was blocked when this request was
            // submitted; do not alter a later request where the instructor
            // intentionally left the student unselected.
            ->when(
                $block->approved_at ?? $block->rejected_at,
                fn ($query, $unblockedAt) => $query->where('requested_at', '<=', $unblockedAt),
            )
            ->lockForUpdate()
            ->first();

        if (! $request) {
            return;
        }

        $studentIds = collect($request->requested_student_ids ?? [])
            ->map(fn ($studentId): int => (int) $studentId)
            ->filter()
            ->push((int) $block->student_id)
            ->unique()
            ->values();

        if ($studentIds->count() === count($request->requested_student_ids ?? [])) {
            return;
        }

        $request->update([
            'requested_student_ids' => $studentIds->all(),
            'student_count' => $studentIds->count(),
        ]);
    }
}
