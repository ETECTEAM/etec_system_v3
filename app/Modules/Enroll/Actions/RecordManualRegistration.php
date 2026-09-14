<?php

namespace App\Modules\Enroll\Actions;

use App\Models\Course;
use App\Models\Term;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Records a hand-entered ("Manual Register") registration: a student plus a
 * class-less enrollment marked source = manual, already paid. It shows up in
 * the Registrations tab with the [Manual] badge and a printable receipt.
 *
 * Course / term are matched to real ids when the name lines up, otherwise
 * left null — the row is a historical record, not something to schedule.
 */
class RecordManualRegistration
{
    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(array $data): stdClass
    {
        return DB::transaction(function () use ($data): stdClass {
            $student = $this->registrations->createStudent([
                'name' => $data['full_name'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
            ], auth()->id());

            $amountPaid = round((float) $data['amount_paid'], 2);
            $documentFee = round((float) ($data['document_price'] ?? 0), 2);

            return $this->registrations->createEnrollment([
                'study_class_id' => null,
                'student_id' => $student->id,
                'course_id' => Course::query()->where('title', $data['course'])->value('id'),
                'term_id' => empty($data['term'])
                    ? null
                    : Term::query()->where('term_name', $data['term'])->value('id'),
                'time_id' => null,
                'enrollment_status' => 'unassigned',
                'payment_status' => 'paid',
                'source' => 'manual',
                'fee_amount' => $amountPaid,
                'document_fee_amount' => $documentFee,
                'amount_paid' => $amountPaid,
                'enrolled_at' => empty($data['payment_date']) ? now() : $data['payment_date'],
                'paid_at' => now(),
            ]);
        });
    }
}
