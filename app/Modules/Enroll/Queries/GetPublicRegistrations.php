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
            ->where('enrollment_status', 'active')
            ->with([
                'student:id,full_name,gender,phone',
                'studyClass:id,title,course_id,term_id,time_id,teacher_id,room_id',
                'studyClass.course:id,title',
                'studyClass.term:id,term_name',
                'studyClass.time:id,time_name',
                'studyClass.teacher:id,name',
                'studyClass.room:id,floor_id,room_number',
                'studyClass.room.floor:id,building_id,name,level',
                'studyClass.room.floor.building:id,name',
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
            'name' => $enrollment->student?->full_name ?? '-',
            'gender' => $enrollment->student?->gender ?? '-',
            'phone' => $enrollment->student?->phone ?? '-',
            'class_id' => $enrollment->study_class_id,
            'class_title' => $enrollment->studyClass?->title ?? '-',
            'course_title' => $enrollment->studyClass?->course?->title,
            'teacher_name' => $enrollment->studyClass?->teacher?->name,
            'building' => $enrollment->studyClass?->room?->floor?->building?->name,
            'floor' => $enrollment->studyClass?->room?->floor?->name,
            'room' => $enrollment->studyClass?->room?->room_number ?? ($enrollment->studyClass?->isOnline() ? 'Online' : null),
            'study_days' => $enrollment->studyClass?->scheduleStudyDays() ?? [],
            'start_time' => $this->formatTime($enrollment->studyClass?->scheduleStartTime()),
            'end_time' => $this->formatTime($enrollment->studyClass?->scheduleEndTime()),
            'fee_amount' => (float) $enrollment->fee_amount,
            'document_fee_amount' => (float) $enrollment->document_fee_amount,
            'amount_paid' => (float) $enrollment->amount_paid,
            'payment_status' => ucfirst($enrollment->payment_status),
            'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d h:i A'),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
