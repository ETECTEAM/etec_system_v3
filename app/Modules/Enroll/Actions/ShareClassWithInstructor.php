<?php

namespace App\Modules\Enroll\Actions;

use App\Models\StudyClass;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Splits one class between its owner and a second instructor, each teaching their own
 * days — e.g. Code on Mon & Tue, Network on Wed & Thu. Both then see the class on their
 * dashboard, showing their own schedule, and both can take its attendance.
 */
class ShareClassWithInstructor
{
    public function __construct(private readonly InstructorAssignmentAvailability $instructorAvailability) {}

    public function handle(StudyClass $studyClass, array $data): StudyClass
    {
        $ownerId = (int) $studyClass->teacher_id;
        $instructorId = (int) $data['instructor_id'];

        if ($ownerId === $instructorId) {
            throw ValidationException::withMessages([
                'instructor_id' => 'Pick a different instructor to share this class with.',
            ]);
        }

        if ($ownerId === 0) {
            throw ValidationException::withMessages([
                'instructor_id' => 'Assign an instructor to this class before sharing it.',
            ]);
        }

        return DB::transaction(function () use ($studyClass, $data, $ownerId, $instructorId): StudyClass {
            $this->ensureInstructorCanTeach($studyClass, $ownerId, (int) ($data['owner_term_id'] ?? $studyClass->term_id));
            $this->ensureInstructorCanTeach($studyClass, $instructorId, (int) $data['instructor_term_id']);

            // Only the days differ between the two halves — both keep the class's time slot.
            $studyClass->instructors()->syncWithoutDetaching([
                $ownerId => [
                    'term_id' => $data['owner_term_id'] ?? $studyClass->term_id,
                    'time_id' => $studyClass->time_id,
                    'subject' => $data['owner_subject'] ?? null,
                ],
                $instructorId => [
                    'term_id' => $data['instructor_term_id'] ?? null,
                    'time_id' => $studyClass->time_id,
                    'subject' => $data['instructor_subject'] ?? null,
                ],
            ]);

            return $studyClass->load('instructors');
        });
    }

    // Each half of a collapsed class must be independently teachable: the owner on
    // their chosen days and the new instructor on theirs, at the class's time slot.
    // The class itself is excluded from the conflict check so editing an existing
    // share doesn't collide with the very class being shared.
    private function ensureInstructorCanTeach(StudyClass $studyClass, int $userId, int $termId): void
    {
        $field = $userId === (int) $studyClass->teacher_id ? 'owner_term_id' : 'instructor_id';

        $reason = $this->instructorAvailability->unavailableReason(
            $userId,
            $termId,
            (int) $studyClass->time_id,
            $studyClass->id,
        );

        if ($reason !== null) {
            throw ValidationException::withMessages([$field => $reason]);
        }
    }

    /**
     * Removes a shared instructor. Dropping everyone but the owner leaves the class
     * unshared, so the owner's now-pointless slot goes too and the class schedule rules again.
     */
    public function remove(StudyClass $studyClass, int $instructorId): StudyClass
    {
        return DB::transaction(function () use ($studyClass, $instructorId): StudyClass {
            if ($instructorId === (int) $studyClass->teacher_id) {
                throw ValidationException::withMessages([
                    'instructor_id' => 'The class owner cannot be removed. Switch the teacher instead.',
                ]);
            }

            $studyClass->instructors()->detach($instructorId);

            $remaining = $studyClass->instructors()
                ->wherePivot('user_id', '!=', $studyClass->teacher_id)
                ->count();

            if ($remaining === 0) {
                $studyClass->instructors()->detach($studyClass->teacher_id);
            }

            return $studyClass->load('instructors');
        });
    }
}
