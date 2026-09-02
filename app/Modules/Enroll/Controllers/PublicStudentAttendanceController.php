<?php

namespace App\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicStudentAttendanceController extends Controller
{
    // Public, unauthenticated landing for the receipt QR code. Read-only, and
    // deliberately free of financial (fees/payments) and contact (phone/address)
    // fields so a scanned receipt only ever exposes attendance.
    public function show(StudentEnrollment $enrollment): Response
    {
        $enrollment->load([
            'student:id,full_name,gender',
            'studyClass:id,title,slug,course_id,term_id,time_id',
            'studyClass.course:id,title',
            'studyClass.term:id,term_name',
            'studyClass.time:id,time_name',
        ]);

        $class = $enrollment->studyClass;

        $records = $class
            ? StudentAttendance::query()
                ->where('study_class_id', $class->id)
                ->where('student_id', $enrollment->student_id)
                ->orderByDesc('attendance_date')
                ->orderByDesc('id')
                ->get(['attendance_date', 'status', 'verification_status'])
            : new Collection;

        return Inertia::render('frontend/student-attendance/Show', [
            'enrollment' => [
                'reference' => 'ETEC'.str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT),
                'enrolled_at' => optional($enrollment->enrolled_at)->format('Y-m-d'),
                'student' => [
                    'id' => $enrollment->student?->id,
                    'name' => $enrollment->student?->full_name ?? '-',
                ],
                'class' => [
                    'title' => $class?->title ?? '-',
                    'course' => $class?->course?->title ?? '-',
                    'term' => $class?->term?->term_name ?? '-',
                    'time' => $class?->time?->time_name ?? '-',
                ],
            ],
            'attendances' => $records->map(fn (StudentAttendance $row): array => [
                'date' => optional($row->attendance_date)->format('Y-m-d'),
                'status' => $row->status,
                'verification_status' => $row->verification_status,
            ])->values(),
            'stats' => [
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'permission' => $records->where('status', 'permission')->count(),
                'total' => $records->count(),
            ],
        ]);
    }
}
