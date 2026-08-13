<?php

namespace App\Modules\Website\Actions;

use App\Models\StudyClass;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Notification\Events\NotificationsUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

/**
 * Public, no-login self-registration from the /classes page — same shape as
 * the QR self-registration flow (Enroll\Actions\CreateClassStudent), except
 * the enrollment is tagged source=public_website so the dashboard can list it,
 * and a dashboard Notification is raised to flag it for follow-up/payment.
 */
class RegisterClassStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(StudyClass $studyClass, array $data): stdClass
    {
        $enrollment = DB::transaction(function () use ($studyClass, $data): stdClass {
            $class = $this->registrations->lockStudyClass($studyClass->id);
            $this->registrations->ensureClassHasSeat($class, 'name');

            $student = $this->registrations->findOrCreatePublicStudent($data);

            if ($this->registrations->activeEnrollmentExistsForClass($class->id, $student->id)) {
                throw ValidationException::withMessages([
                    'phone' => 'This phone number is already registered for this class.',
                ]);
            }

            $enrollment = $this->registrations->createEnrollment([
                'study_class_id' => $class->id,
                'student_id' => $student->id,
                'source' => 'public_website',
                'fee_amount' => $class->price,
                'document_fee_amount' => $class->document_price,
            ]);

            DB::table('notifications')->insert([
                'title' => 'New Class Registration',
                'message' => "{$data['name']} registered for \"{$class->title}\".",
                'type' => 'class_registration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $enrollment;
        });

        NotificationsUpdated::dispatch();

        return $enrollment;
    }
}
