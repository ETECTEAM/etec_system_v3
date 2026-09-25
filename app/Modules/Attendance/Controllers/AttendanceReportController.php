<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceReportController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today('Asia/Phnom_Penh');
        $from = $this->date($request->query('from'), $today);
        $to = $this->date($request->query('to'), $from);

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $timeId = $request->integer('time_id') ?: null;
        $courseId = $request->integer('course_id') ?: null;
        $instructorId = $request->integer('instructor_id') ?: null;
        $trackingStatus = $request->string('tracking_status')->toString();
        $trackingStatus = in_array($trackingStatus, ['tracked', 'not_tracked'], true) ? $trackingStatus : null;
        $perPage = $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 75, 100], true) ? $perPage : 10;

        $base = ClassSession::query()
            ->join('study_classes', 'study_classes.id', '=', 'class_sessions.study_class_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->leftJoin('courses', 'courses.id', '=', 'study_classes.course_id')
            ->leftJoin('users as instructors', 'instructors.id', '=', 'study_classes.teacher_id')
            ->leftJoin('student_attendances', function ($join) {
                $join->on('student_attendances.study_class_id', '=', 'class_sessions.study_class_id')
                    ->whereColumn('student_attendances.attendance_date', '=', 'class_sessions.session_date');
            })
            ->whereBetween('class_sessions.session_date', [$from->toDateString(), $to->toDateString()])
            ->when($timeId, fn ($query) => $query->where('study_classes.time_id', $timeId))
            ->when($courseId, fn ($query) => $query->where('study_classes.course_id', $courseId))
            ->when($instructorId, fn ($query) => $query->where('study_classes.teacher_id', $instructorId));

        $rows = (clone $base)->toBase()
            ->selectRaw("class_sessions.id as session_id, class_sessions.study_class_id, class_sessions.session_date as date, COALESCE(times.time_name, 'No time') as time, COALESCE(courses.title, study_classes.title, 'Untitled class') as course, COALESCE(instructors.name, 'No instructor') as instructor_name, COUNT(student_attendances.id) as total_students, SUM(CASE WHEN student_attendances.present = 1 THEN 1 ELSE 0 END) as present, SUM(CASE WHEN student_attendances.absent = 1 THEN 1 ELSE 0 END) as absent, SUM(CASE WHEN student_attendances.late = 1 THEN 1 ELSE 0 END) as late, SUM(CASE WHEN student_attendances.permission = 1 THEN 1 ELSE 0 END) as permission")
            ->groupBy('class_sessions.id', 'class_sessions.study_class_id', 'class_sessions.session_date', 'study_classes.time_id', 'times.time_name', 'courses.title', 'study_classes.title', 'instructors.name')
            ->when($trackingStatus === 'tracked', fn ($query) => $query->havingRaw('COUNT(student_attendances.id) > 0'))
            ->when($trackingStatus === 'not_tracked', fn ($query) => $query->havingRaw('COUNT(student_attendances.id) = 0'))
            ->orderByDesc('class_sessions.session_date')
            ->orderBy('times.time_name')
            ->orderBy('class_sessions.id');

        $allRows = (clone $rows)->get();
        $summary = [
            'total_classes' => $allRows->pluck('study_class_id')->unique()->count(),
            'total_class_tracked' => $allRows->filter(fn ($row) => (int) $row->total_students > 0)->count(),
            'present' => (int) $allRows->sum('present'),
            'absent' => (int) $allRows->sum('absent'),
            'late' => (int) $allRows->sum('late'),
            'permission' => (int) $allRows->sum('permission'),
        ];
        $rows = $rows->paginate($perPage)->withQueryString();

        return Inertia::render('backend/attendance-report/Index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'time_id' => $timeId, 'course_id' => $courseId, 'instructor_id' => $instructorId, 'tracking_status' => $trackingStatus, 'per_page' => $perPage],
            'timeOptions' => DB::table('times')->orderBy('time_name')->get(['id', 'time_name']),
            'courseOptions' => DB::table('courses')->whereNotNull('title')->orderBy('title')->get(['id', 'title']),
            'instructorOptions' => User::role('instructor')->orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
            'rows' => $rows,
        ]);
    }

    private function date(?string $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
