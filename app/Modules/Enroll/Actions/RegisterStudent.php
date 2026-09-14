<?php

namespace App\Modules\Enroll\Actions;

use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Pre-registers a student with no class assigned yet. A matching classless
 * enrollment keeps the row visible in Registrations so admins can collect
 * payment, print a receipt, and assign the student into a class later.
 */
class RegisterStudent
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(array $data): stdClass
    {
        return DB::transaction(function () use ($data): stdClass {
            $student = $this->registrations->createStudent($data, auth()->id());

            $this->registrations->createEnrollment([
                'study_class_id' => null,
                'student_id' => $student->id,
                'course_id' => $data['course_id'],
                'term_id' => $data['term_id'],
                'time_id' => $data['time_id'],
                'enrollment_status' => 'unassigned',
                'source' => 'admin_register',
                'fee_amount' => $data['price'],
                'unit_price' => $data['unit_price'] ?? null,
                'document_fee_amount' => $data['document_price'] ?? 0,
                'enrolled_at' => now(),
                'no_room_and_instructor' => true,
            ]);

            return $student;
        });
    }
}
