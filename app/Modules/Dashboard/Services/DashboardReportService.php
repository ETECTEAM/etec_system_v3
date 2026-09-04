<?php

namespace App\Modules\Dashboard\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReportService
{
    public function handle(Request $request): array
    {
        $filters = $this->filters($request);
        $current = $this->period($filters);
        $previous = $this->previousPeriod($current['start'], $current['end']);

        return [
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'summary' => $this->summary($current, $previous, $filters),
            'enrollmentTrend' => $this->enrollmentTrend($current, $filters),
            'revenueTrend' => $this->revenueTrend($current, $filters),
            'courseStats' => $this->courseStats($current, $filters),
            'paymentStatus' => $this->paymentStatus($current, $filters),
        ];
    }

    private function filters(Request $request): array
    {
        $quick = $request->string('quick')->toString() ?: 'all_time';
        $currentYear = now('Asia/Phnom_Penh')->year;
        $year = $request->input('year') !== null && $request->input('year') !== ''
            ? (int) $request->input('year')
            : null;
        $year = $year !== null && $year >= 2018 && $year <= $currentYear ? $year : null;

        return [
            'quick' => $quick,
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'year' => $year,
        ];
    }

    private function period(array $filters): array
    {
        $today = now('Asia/Phnom_Penh');

        if ($filters['quick'] === 'custom') {
            $start = $filters['start_date']
                ? Carbon::parse($filters['start_date'], 'Asia/Phnom_Penh')->startOfDay()
                : $today->copy()->startOfMonth();
            $end = $filters['end_date']
                ? Carbon::parse($filters['end_date'], 'Asia/Phnom_Penh')->endOfDay()
                : $today->copy()->endOfDay();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                'start' => $start,
                'end' => $end,
            ];
        }

        if ($filters['year']) {
            $date = Carbon::create($filters['year'], 1, 1, 0, 0, 0, 'Asia/Phnom_Penh');

            return ['start' => $date->copy()->startOfYear(), 'end' => $date->copy()->endOfYear()];
        }

        return match ($filters['quick']) {
            'today' => ['start' => $today->copy()->startOfDay(), 'end' => $today->copy()->endOfDay()],
            'this_week' => ['start' => $today->copy()->startOfWeek(), 'end' => $today->copy()->endOfWeek()],
            'this_month' => ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->endOfMonth()],
            'this_year' => ['start' => $today->copy()->startOfYear(), 'end' => $today->copy()->endOfYear()],
            default => ['start' => Carbon::create(2018, 1, 1, 0, 0, 0, 'Asia/Phnom_Penh'), 'end' => $today->copy()->endOfDay()],
        };
    }

    private function previousPeriod(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;

        return [
            'start' => $start->copy()->subDays($days),
            'end' => $start->copy()->subSecond(),
        ];
    }

    private function summary(array $current, array $previous, array $filters): array
    {
        $enrollments = $this->enrollmentBase($filters);
        $revenue = $this->revenueBase($filters);

        $totalEnrolled = (clone $enrollments)->whereBetween('student_enrollments.enrolled_at', [$current['start'], $current['end']])->count();
        $previousEnrolled = (clone $this->enrollmentBase($filters))->whereBetween('student_enrollments.enrolled_at', [$previous['start'], $previous['end']])->count();
        $newEnrollments = (clone $enrollments)->whereBetween('student_enrollments.created_at', [$current['start'], $current['end']])->count();
        $totalRevenue = (float) (clone $revenue)->whereBetween('student_enrollments.paid_at', [$current['start'], $current['end']])->sum('student_enrollments.amount_paid');
        $previousRevenue = (float) (clone $this->revenueBase($filters))->whereBetween('student_enrollments.paid_at', [$previous['start'], $previous['end']])->sum('student_enrollments.amount_paid');
        $paidEnrollments = (clone $revenue)->whereBetween('student_enrollments.paid_at', [$current['start'], $current['end']])->count();
        $previousPaidEnrollments = (clone $this->revenueBase($filters))->whereBetween('student_enrollments.paid_at', [$previous['start'], $previous['end']])->count();
        $previousNewEnrollments = (clone $this->enrollmentBase($filters))->whereBetween('student_enrollments.created_at', [$previous['start'], $previous['end']])->count();
        $averageRevenue = $paidEnrollments > 0 ? round($totalRevenue / $paidEnrollments, 2) : 0;
        $previousAverageRevenue = $previousPaidEnrollments > 0 ? round($previousRevenue / $previousPaidEnrollments, 2) : 0;
        $outstanding = (float) (clone $enrollments)
            ->whereBetween('student_enrollments.enrolled_at', [$current['start'], $current['end']])
            ->whereIn('student_enrollments.payment_status', ['unpaid', 'partial'])
            ->sum(DB::raw('GREATEST((student_enrollments.fee_amount + student_enrollments.document_fee_amount) - student_enrollments.amount_paid, 0)'));

        return [
            'period_label' => $current['start']->format('M d, Y').' - '.$current['end']->format('M d, Y'),
            'total_students_enrolled' => $totalEnrolled,
            'total_revenue_collected' => $totalRevenue,
            'new_enrollments' => $newEnrollments,
            'average_revenue_per_enrollment' => $averageRevenue,
            'outstanding_amount' => $outstanding,
            'paid_enrollments' => $paidEnrollments,
            'enrollment_change_percent' => $this->percentChange($totalEnrolled, $previousEnrolled),
            'revenue_change_percent' => $this->percentChange($totalRevenue, $previousRevenue),
            'new_enrollment_change_percent' => $this->percentChange($newEnrollments, $previousNewEnrollments),
            'average_revenue_change_percent' => $this->percentChange($averageRevenue, $previousAverageRevenue),
        ];
    }

    private function enrollmentTrend(array $period, array $filters): array
    {
        return $this->trend($this->enrollmentBase($filters), 'student_enrollments.enrolled_at', 'COUNT(*)', $period);
    }

    private function revenueTrend(array $period, array $filters): array
    {
        return $this->trend($this->revenueBase($filters), 'student_enrollments.paid_at', 'SUM(student_enrollments.amount_paid)', $period);
    }

    private function trend($query, string $dateColumn, string $aggregate, array $period): array
    {
        $group = $this->grouping($period['start'], $period['end']);
        $format = match ($group) {
            'hour' => '%Y-%m-%d %H:00:00',
            'month' => '%Y-%m-01',
            default => '%Y-%m-%d',
        };

        $rows = $query
            ->whereBetween($dateColumn, [$period['start'], $period['end']])
            ->selectRaw("DATE_FORMAT({$dateColumn}, ?) as bucket, {$aggregate} as value", [$format])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('value', 'bucket');

        return collect($this->buckets($period['start'], $period['end'], $group))
            ->map(fn (array $bucket) => [
                'label' => $bucket['label'],
                'value' => round((float) ($rows[$bucket['key']] ?? 0), 2),
            ])
            ->all();
    }

    private function courseStats(array $period, array $filters): array
    {
        return $this->enrollmentBase($filters)
            ->whereBetween('student_enrollments.enrolled_at', [$period['start'], $period['end']])
            ->selectRaw('COALESCE(enrollment_courses.title, class_courses.title, "Unassigned") as course_title')
            ->selectRaw('COUNT(*) as enrollments')
            ->selectRaw(
                'SUM(CASE WHEN student_enrollments.payment_status IN ("paid", "partial") AND student_enrollments.paid_at BETWEEN ? AND ? THEN student_enrollments.amount_paid ELSE 0 END) as revenue',
                [$period['start'], $period['end']]
            )
            ->groupBy('course_title')
            ->orderByDesc('enrollments')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'course_title' => $row->course_title,
                'enrollments' => (int) $row->enrollments,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    private function paymentStatus(array $period, array $filters): array
    {
        $colors = [
            'paid' => '#10b981',
            'partial' => '#f59e0b',
            'unpaid' => '#f59e0b',
            'cancelled' => '#ef4444',
            'refunded' => '#a855f7',
            'pending' => '#f59e0b',
        ];

        $rows = $this->enrollmentBase($filters)
            ->whereBetween('student_enrollments.enrolled_at', [$period['start'], $period['end']])
            ->selectRaw('student_enrollments.payment_status as status, COUNT(*) as total')
            ->whereNotNull('student_enrollments.payment_status')
            ->groupBy('student_enrollments.payment_status')
            ->orderByDesc('total')
            ->get();

        $total = (int) $rows->sum('total');

        return [
            'total' => $total,
            'items' => $rows
                ->map(fn ($row) => [
                    'status' => (string) $row->status,
                    'label' => str((string) $row->status)->replace('_', ' ')->title()->toString(),
                    'count' => (int) $row->total,
                    'percent' => $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0,
                    'color' => $colors[(string) $row->status] ?? '#64748b',
                ])
                ->values()
                ->all(),
        ];
    }

    private function enrollmentBase(array $filters)
    {
        return DB::table('student_enrollments')
            ->leftJoin('study_classes', 'study_classes.id', '=', 'student_enrollments.study_class_id')
            ->leftJoin('courses as class_courses', 'class_courses.id', '=', 'study_classes.course_id')
            ->leftJoin('courses as enrollment_courses', 'enrollment_courses.id', '=', 'student_enrollments.course_id')
            ->whereIn('student_enrollments.enrollment_status', ['active', 'pending', 'unassigned', 'completed']);
    }

    private function revenueBase(array $filters)
    {
        return $this->enrollmentBase($filters)
            ->whereIn('student_enrollments.payment_status', ['paid', 'partial'])
            ->where('student_enrollments.amount_paid', '>', 0)
            ->whereNotNull('student_enrollments.paid_at');
    }

    private function filterOptions(): array
    {
        return [
            'years' => collect(range(2018, now('Asia/Phnom_Penh')->year))->values()->all(),
        ];
    }

    private function grouping(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return 'hour';
        }

        return $start->diffInDays($end) > 120 ? 'month' : 'day';
    }

    private function buckets(Carbon $start, Carbon $end, string $group): array
    {
        $cursor = $start->copy();
        $buckets = [];

        while ($cursor <= $end) {
            $buckets[] = [
                'key' => match ($group) {
                    'hour' => $cursor->format('Y-m-d H:00:00'),
                    'month' => $cursor->format('Y-m-01'),
                    default => $cursor->format('Y-m-d'),
                },
                'label' => match ($group) {
                    'hour' => $cursor->format('H:00'),
                    'month' => $cursor->format('M'),
                    default => $cursor->format('M d'),
                },
            ];

            match ($group) {
                'hour' => $cursor->addHour(),
                'month' => $cursor->addMonthNoOverflow()->startOfMonth(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    private function percentChange(int|float $current, int|float $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
