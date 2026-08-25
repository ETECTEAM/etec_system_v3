<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\OfficialLeave;
use App\Models\StudentPermission;
use Illuminate\Support\Carbon;

/**
 * Super-admin report/stats reads: monthly trend, per-class breakdown, the
 * permission-quota watchlist, and who is on approved leave right now.
 */
class OfficialLeaveReportService
{
    /**
     * Approved official leaves per month over the trailing $months window.
     *
     * @return list<array{month: string, label: string, count: int}>
     */
    public function leavesPerMonth(int $months = 12): array
    {
        $start = Carbon::now('Asia/Phnom_Penh')->startOfMonth()->subMonths($months - 1);

        $rows = OfficialLeave::query()
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereDate('approved_at', '>=', $start->toDateString())
            ->selectRaw("date_format(approved_at, '%Y-%m') as month")
            ->selectRaw('count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $series = [];

        foreach (range(0, $months - 1) as $i) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $series[] = [
                'month' => $key,
                'label' => $month->format('M Y'),
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Approved leaves broken down by the class's course; class-scoped rows join through
     * study_classes, blanket rows (null class = every class) land in their own bucket.
     *
     * @return list<array{label: string, count: int}>
     */
    public function leavesPerCourse(int $limit = 12): array
    {
        return OfficialLeave::query()
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->leftJoin('study_classes', 'study_classes.id', '=', 'official_leaves.study_class_id')
            ->leftJoin('courses', 'courses.id', '=', 'study_classes.course_id')
            ->selectRaw("coalesce(courses.title, study_classes.title, 'All classes') as label")
            ->selectRaw('count(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->total])
            ->all();
    }

    /**
     * Quota watchlist: students ranked by instructor permissions used this month,
     * shown as X/quota so the office can spot quota burners.
     *
     * @return list<array{id: int, full_name: string, used: int, quota: int}>
     */
    public function topPermissionUsers(int $limit = 10): array
    {
        $quota = max(1, (int) official_leave_setting('monthly_permission_quota'));
        $monthStart = Carbon::now('Asia/Phnom_Penh')->startOfMonth()->toDateString();
        $monthEnd = Carbon::now('Asia/Phnom_Penh')->endOfMonth()->toDateString();

        return StudentPermission::query()
            ->join('students', 'students.id', '=', 'student_permissions.student_id')
            ->whereDate('start_date', '>=', $monthStart)
            ->whereDate('start_date', '<=', $monthEnd)
            ->selectRaw('students.id, students.full_name, count(*) as used')
            ->groupBy('students.id', 'students.full_name')
            ->orderByDesc('used')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'full_name' => $row->full_name,
                'used' => (int) $row->used,
                'quota' => $quota,
            ])
            ->all();
    }

    /**
     * Students currently on an approved leave (today, Asia/Phnom_Penh).
     */
    public function onLeaveToday(): array
    {
        $today = Carbon::today('Asia/Phnom_Penh')->toDateString();

        $leaves = OfficialLeave::query()
            ->with(['student:id,full_name,user_id', 'student.user.photo', 'studyClass:id,title'])
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('end_date')
            ->get();

        return $leaves->map(fn (OfficialLeave $leave) => [
            'leave_id' => $leave->id,
            'student_id' => $leave->student?->id,
            'full_name' => $leave->student?->full_name ?? '(deleted student)',
            'photo_url' => $leave->student?->user?->photo?->url,
            'class' => $leave->studyClass?->title,
            'start_date' => $leave->start_date?->toDateString(),
            'end_date' => $leave->end_date?->toDateString(),
        ])->all();
    }
}
