<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\OfficialLeave;
use App\Models\OfficialLeaveSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getLeavesPerMonth(int $months = 12): array
    {
        return OfficialLeave::select(
            DB::raw("DATE_FORMAT(start_date, '%Y-%m') as month"),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
            DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
        )
            ->where('start_date', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getTopStudents(int $limit = 10): array
    {
        $quota = (int) OfficialLeaveSetting::where('key', 'monthly_permission_quota')->value('value') ?? 4;

        return OfficialLeave::select('student_id', DB::raw('COUNT(*) as total'))
            ->with('student')
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->groupBy('student_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($quota) {
                return [
                    'student_id' => $item->student_id,
                    'name' => $item->student->full_name ?? 'Unknown',
                    'used' => $item->total,
                    'quota' => $quota,
                    'percentage' => $quota > 0 ? round(($item->total / $quota) * 100) : 0,
                ];
            })
            ->toArray();
    }

    public function getCurrentlyOnLeave(): array
    {
        return OfficialLeave::with(['student.user', 'approver'])
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->toArray();
    }

    public function getClassBreakdown(): array
    {
        return OfficialLeave::select('student_id', DB::raw('COUNT(*) as total'))
            ->with('student.enrollments.scheduleClass')
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->groupBy('student_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                $classes = $item->student->enrollments->pluck('scheduleClass.title')->filter()->unique()->values();
                return [
                    'student' => $item->student->full_name,
                    'total' => $item->total,
                    'classes' => $classes,
                ];
            })
            ->toArray();
    }
}
