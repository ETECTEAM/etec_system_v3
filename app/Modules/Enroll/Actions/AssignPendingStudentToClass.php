<?php

namespace App\Modules\Enroll\Actions;

use App\Models\PendingRegistration;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class AssignPendingStudentToClass
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    // Resolves a parked public registration (see
    // RegisterStudentForSchedule::savePendingRegistration) by force-assigning
    // the student into an admin-chosen class. The 2-week rule is re-checked
    // here against live data rather than trusting the meta snapshot - classes
    // age out of eligibility between registration and resolution.
    public function handle(int $pendingRegistrationId, int $studyClassId, bool $overrideCapacity = true): stdClass
    {
        return DB::transaction(function () use ($pendingRegistrationId, $studyClassId, $overrideCapacity): stdClass {
            $pending = PendingRegistration::query()->lockForUpdate()->findOrFail($pendingRegistrationId);

            if ($pending->status !== 'pending') {
                throw ValidationException::withMessages([
                    'pending_registration_id' => 'This pending registration has already been resolved.',
                ]);
            }

            $studyClass = $this->registrations->lockStudyClass($studyClassId);

            if (! $this->meetsTwoWeekRule($studyClass)) {
                throw ValidationException::withMessages([
                    'study_class_id' => 'This class is not eligible: it must have been created within the last two weeks or start within two weeks of today.',
                ]);
            }

            $this->registrations->ensureStudentIsNotEnrolledInClass((int) $studyClass->id, (int) $pending->student_id);

            // override_capacity=true deliberately overbooks: capacity is bumped
            // to fit instead of leaving the class reading over 100% filled.
            if ($overrideCapacity) {
                $this->registrations->expandCapacityToFit($studyClass);
            } else {
                $this->registrations->ensureClassHasSeat($studyClass);
            }

            $enrollment = $this->registrations->createEnrollment([
                'study_class_id' => $studyClass->id,
                'student_id' => $pending->student_id,
                'source' => 'admin_force_assignment',
                'fee_amount' => $studyClass->price,
                'document_fee_amount' => $studyClass->document_price,
            ]);

            $pending->update(['status' => 'resolved']);

            return $enrollment;
        });
    }

    // Same rule as findEligibleClassesForAdmin(): created within the last
    // 2 weeks, OR start_date within [now - 2 weeks, now + 2 weeks]. Works on
    // the stdClass row lockStudyClass() returns, whose dates are plain
    // strings rather than cast Carbons.
    private function meetsTwoWeekRule(stdClass $studyClass): bool
    {
        $twoWeeksAgo = now()->subWeeks(2);
        $twoWeeksAhead = now()->addWeeks(2);

        $createdAt = $studyClass->created_at !== null ? Carbon::parse($studyClass->created_at) : null;
        $startDate = $studyClass->start_date !== null ? Carbon::parse($studyClass->start_date) : null;

        return ($createdAt !== null && $createdAt->gte($twoWeeksAgo))
            || ($startDate !== null && $startDate->between($twoWeeksAgo, $twoWeeksAhead));
    }
}
