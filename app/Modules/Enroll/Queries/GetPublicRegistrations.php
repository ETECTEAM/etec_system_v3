<?php

namespace App\Modules\Enroll\Queries;

use App\Models\StudentEnrollment;

/**
 * Lists students who self-registered via the public /classes page
 * (StudentEnrollment.source = 'public_website'), shaped for the dashboard's
 * "Registrations" tab and its receipt-printing action.
 */
class GetPublicRegistrations
{
    public function handle(int $limit = 50): array
    {
        return StudentEnrollment::query()
            ->where('source', 'public_website')
            ->with([
                'student:id,name',
                'student.student:id,user_id,gender,phone',
                'studyClass:id,title,course_id,study_days,start_time,end_time',
                'studyClass.course:id,title',
            ])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (StudentEnrollment $enrollment) => $this->present($enrollment))
            ->values()
            ->all();
    }

    private function present(StudentEnrollment $enrollment): array
    {
        return [
            'enrollment_id' => $enrollment->id,
            'name' => $enrollment->student?->name ?? '-',
            'gender' => $enrollment->student?->student?->gender ?? '-',
            'phone' => $enrollment->student?->student?->phone ?? '-',
            'class_id' => $enrollment->study_class_id,
            'class_title' => $enrollment->studyClass?->title ?? '-',
            'course_title' => $enrollment->studyClass?->course?->title,
            'study_days' => $enrollment->studyClass?->study_days ?? [],
            'start_time' => $this->formatTime($enrollment->studyClass?->start_time),
            'end_time' => $this->formatTime($enrollment->studyClass?->end_time),
            'fee_amount' => (float) $enrollment->fee_amount,
            'document_fee_amount' => (float) $enrollment->document_fee_amount,
            'amount_paid' => (float) $enrollment->amount_paid,
            'payment_status' => ucfirst($enrollment->payment_status),
            'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d'),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
