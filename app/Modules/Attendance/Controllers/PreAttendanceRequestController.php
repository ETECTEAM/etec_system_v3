<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\PreAttendanceRequest;
use App\Models\User;
use App\Modules\Attendance\Events\PreAttendanceRequestUpdated;
use App\Support\InstructorDisplayName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreAttendanceRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('backend/pre-attendance-requests/Index', [
            'requests' => $this->requests(),
        ]);
    }

    public function classes(): Response
    {
        return Inertia::render('backend/pre-attendance-requests/Classes', [
            'preAttendanceClasses' => $this->preAttendanceClasses(),
        ]);
    }

    public function counts(): Response
    {
        return Inertia::render('backend/pre-attendance-requests/Counts', [
            'instructorStats' => $this->instructorStats(),
        ]);
    }

    public function detail(User $instructor): Response
    {
        return Inertia::render('backend/pre-attendance-requests/Detail', [
            'instructor' => [
                'id' => $instructor->id,
                'name' => InstructorDisplayName::format($instructor->name),
            ],
            'sessions' => $this->instructorSessionDetails($instructor),
        ]);
    }

    public function update(Request $request, PreAttendanceRequest $preAttendanceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([PreAttendanceRequest::STATUS_APPROVED, PreAttendanceRequest::STATUS_REJECTED])],
        ]);

        if (! in_array($preAttendanceRequest->status, [PreAttendanceRequest::STATUS_PENDING, PreAttendanceRequest::STATUS_APPROVED], true)) {
            return back()->with('warning', 'This pre-attendance request has already been completed.');
        }

        $preAttendanceRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($preAttendanceRequest->status === PreAttendanceRequest::STATUS_APPROVED) {
            PreAttendanceRequestUpdated::dispatch(
                $preAttendanceRequest->study_class_id,
                $preAttendanceRequest->id,
                $preAttendanceRequest->status,
            );
        }

        return back()->with('success', 'Pre-attendance request updated successfully.');
    }

    public function approveClass(Request $request, ClassSession $classSession): RedirectResponse
    {
        if (! in_array($classSession->status, [ClassSession::STATUS_PRE_ATTENDANCE, ClassSession::STATUS_PARTIAL], true)) {
            return back()->with('warning', 'This class is not in pre-attendance recovery.');
        }

        if (! $classSession->instructor_id) {
            return back()->with('warning', 'This pre-attendance class has no instructor assigned.');
        }

        $preAttendanceRequest = PreAttendanceRequest::query()->updateOrCreate(
            [
                'study_class_id' => $classSession->study_class_id,
                'session_date' => $classSession->session_date->toDateString(),
                'requested_by' => $classSession->instructor_id,
            ],
            [
                'class_session_id' => $classSession->id,
                'session_status' => $classSession->status,
                'status' => PreAttendanceRequest::STATUS_APPROVED,
                'note' => 'Approved by admin before instructor request.',
                'requested_at' => now(),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'completed_at' => null,
            ],
        );

        PreAttendanceRequestUpdated::dispatch(
            $preAttendanceRequest->study_class_id,
            $preAttendanceRequest->id,
            $preAttendanceRequest->status,
        );

        return back()->with('success', 'Pre-attendance class approved successfully.');
    }

    private function requests(): array
    {
        return PreAttendanceRequest::query()
            ->with(['studyClass:id,title', 'requestedBy:id,name', 'reviewedBy:id,name'])
            ->latest('requested_at')
            ->limit(100)
            ->get()
            ->map(fn (PreAttendanceRequest $request): array => [
                'id' => $request->id,
                'class_id' => $request->study_class_id,
                'class_title' => $request->studyClass?->title ?? '-',
                'instructor' => InstructorDisplayName::format($request->requestedBy?->name),
                'session_date' => $request->session_date?->format('Y-m-d'),
                'session_status' => $request->session_status,
                'status' => $request->status,
                'status_label' => ucfirst(str_replace('_', ' ', $request->status)),
                'note' => $request->note,
                'requested_at' => $request->requested_at?->format('Y-m-d H:i'),
                'reviewed_by' => $request->reviewedBy?->name,
                'reviewed_at' => $request->reviewed_at?->format('Y-m-d H:i'),
                'completed_at' => $request->completed_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    private function preAttendanceClasses(): array
    {
        $activeCounts = DB::table('student_enrollments')
            ->select('study_class_id', DB::raw('count(*) as total_students'))
            ->where('enrollment_status', 'active')
            ->groupBy('study_class_id');
        $trackedCounts = DB::table('student_attendances')
            ->select('study_class_id', 'attendance_date', DB::raw('count(*) as tracked_count'))
            ->groupBy('study_class_id', 'attendance_date');

        return DB::table('class_sessions')
            ->join('study_classes', 'study_classes.id', '=', 'class_sessions.study_class_id')
            ->leftJoin('users as instructors', 'instructors.id', '=', 'class_sessions.instructor_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->leftJoinSub($activeCounts, 'active_counts', fn ($join) => $join->on('active_counts.study_class_id', '=', 'study_classes.id'))
            ->leftJoinSub($trackedCounts, 'tracked_counts', function ($join) {
                $join->on('tracked_counts.study_class_id', '=', 'study_classes.id')
                    ->whereColumn('tracked_counts.attendance_date', 'class_sessions.session_date');
            })
            ->leftJoin('pre_attendance_requests', function ($join) {
                $join->on('pre_attendance_requests.study_class_id', '=', 'class_sessions.study_class_id')
                    ->whereColumn('pre_attendance_requests.session_date', 'class_sessions.session_date')
                    ->whereColumn('pre_attendance_requests.requested_by', 'class_sessions.instructor_id');
            })
            ->whereIn('class_sessions.status', [ClassSession::STATUS_PRE_ATTENDANCE, ClassSession::STATUS_PARTIAL])
            ->orderByDesc('class_sessions.session_date')
            ->orderBy('class_sessions.scheduled_start')
            ->select([
                'class_sessions.id',
                'class_sessions.session_date',
                'class_sessions.status',
                'study_classes.title',
                'times.time_name',
                'instructors.name as instructor',
                'pre_attendance_requests.status as request_status',
                DB::raw('coalesce(active_counts.total_students, 0) as total_students'),
                DB::raw('coalesce(tracked_counts.tracked_count, 0) as tracked_count'),
            ])
            ->get()
            ->map(function ($row): array {
                $tracked = (int) $row->tracked_count;
                $total = (int) $row->total_students;

                return [
                    'id' => (int) $row->id,
                    'class_title' => $row->title,
                'instructor' => InstructorDisplayName::format($row->instructor ?? null),
                    'session_date' => Carbon::parse($row->session_date)->format('Y-m-d'),
                    'time' => $row->time_name ?? '-',
                    'session_status' => $row->status,
                    'session_status_label' => ucfirst(str_replace('_', ' ', $row->status)),
                    'request_status' => $row->request_status,
                    'request_status_label' => $row->request_status ? ucfirst(str_replace('_', ' ', $row->request_status)) : 'No request',
                    'tracked_count' => $tracked,
                    'total_students' => $total,
                    'unresolved_count' => max(0, $total - $tracked),
                ];
            })
            ->all();
    }

    private function instructorStats(): array
    {
        $currentRows = DB::table('class_sessions')
            ->whereIn('class_sessions.status', [ClassSession::STATUS_PRE_ATTENDANCE, ClassSession::STATUS_PARTIAL])
            ->whereNotNull('instructor_id')
            ->get(['instructor_id', 'study_class_id', 'session_date'])
            ->map(fn ($row) => [
                'instructor_id' => (int) $row->instructor_id,
                'key' => $row->study_class_id.'|'.Carbon::parse($row->session_date)->toDateString(),
            ]);
        $requestRows = PreAttendanceRequest::query()
            ->get(['requested_by', 'study_class_id', 'session_date'])
            ->map(fn (PreAttendanceRequest $request) => [
                'instructor_id' => (int) $request->requested_by,
                'key' => $request->study_class_id.'|'.$request->session_date->toDateString(),
            ]);
        $preAttendanceCounts = $currentRows
            ->merge($requestRows)
            ->unique(fn (array $row) => $row['instructor_id'].'|'.$row['key'])
            ->groupBy('instructor_id')
            ->map(fn ($rows) => $rows->count());

        if ($preAttendanceCounts->isEmpty()) {
            return [];
        }

        $retrackCounts = PreAttendanceRequest::query()
            ->whereIn('requested_by', $preAttendanceCounts->keys())
            ->where('status', PreAttendanceRequest::STATUS_COMPLETED)
            ->select('requested_by', DB::raw('count(*) as retrack_count'))
            ->groupBy('requested_by')
            ->pluck('retrack_count', 'requested_by');

        return User::query()
            ->whereIn('id', $preAttendanceCounts->keys())
            ->orderBy('name')
            ->get()
            ->map(fn (User $instructor): array => [
                'instructor_id' => $instructor->id,
                'instructor' => InstructorDisplayName::format($instructor->name),
                'pre_attendance_count' => (int) ($preAttendanceCounts[$instructor->id] ?? 0),
                'retrack_count' => (int) ($retrackCounts[$instructor->id] ?? 0),
            ])
            ->sortByDesc('pre_attendance_count')
            ->values()
            ->all();
    }

    private function instructorSessionDetails(User $instructor): array
    {
        $sessions = DB::table('class_sessions')
            ->join('study_classes', 'study_classes.id', '=', 'class_sessions.study_class_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->leftJoin('pre_attendance_requests', function ($join) use ($instructor) {
                $join->on('pre_attendance_requests.study_class_id', '=', 'class_sessions.study_class_id')
                    ->whereColumn('pre_attendance_requests.session_date', 'class_sessions.session_date')
                    ->where('pre_attendance_requests.requested_by', '=', $instructor->id);
            })
            ->where('class_sessions.instructor_id', $instructor->id)
            ->whereIn('class_sessions.status', [ClassSession::STATUS_PRE_ATTENDANCE, ClassSession::STATUS_PARTIAL])
            ->orderByDesc('class_sessions.session_date')
            ->orderByDesc('class_sessions.scheduled_start')
            ->select([
                'class_sessions.id',
                'class_sessions.study_class_id',
                'class_sessions.session_date',
                'class_sessions.scheduled_start',
                'class_sessions.scheduled_end',
                'class_sessions.status',
                'study_classes.title',
                'times.time_name',
                'pre_attendance_requests.status as request_status',
                'pre_attendance_requests.requested_at',
                'pre_attendance_requests.reviewed_at',
                'pre_attendance_requests.completed_at',
            ])
            ->get();

        $requestSessions = DB::table('pre_attendance_requests')
            ->join('study_classes', 'study_classes.id', '=', 'pre_attendance_requests.study_class_id')
            ->leftJoin('class_sessions', 'class_sessions.id', '=', 'pre_attendance_requests.class_session_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->where('pre_attendance_requests.requested_by', $instructor->id)
            ->select([
                'pre_attendance_requests.id',
                'pre_attendance_requests.study_class_id',
                'pre_attendance_requests.session_date',
                'class_sessions.scheduled_start',
                'class_sessions.scheduled_end',
                'pre_attendance_requests.session_status as status',
                'study_classes.title',
                'times.time_name',
                'pre_attendance_requests.status as request_status',
                'pre_attendance_requests.requested_at',
                'pre_attendance_requests.reviewed_at',
                'pre_attendance_requests.completed_at',
            ])
            ->get();
        $sessions = $sessions
            ->concat($requestSessions)
            ->unique(fn ($session) => $session->study_class_id.'|'.Carbon::parse($session->session_date)->toDateString())
            ->sortByDesc(fn ($session) => Carbon::parse($session->session_date)->timestamp)
            ->values();

        if ($sessions->isEmpty()) {
            return [];
        }

        $classIds = $sessions->pluck('study_class_id')->unique();
        $activeCounts = DB::table('student_enrollments')
            ->whereIn('study_class_id', $classIds)
            ->where('enrollment_status', 'active')
            ->select('study_class_id', DB::raw('count(*) as total_students'))
            ->groupBy('study_class_id')
            ->pluck('total_students', 'study_class_id');
        $trackedCounts = DB::table('student_attendances')
            ->whereIn('study_class_id', $classIds)
            ->whereIn('attendance_date', $sessions->pluck('session_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->unique())
            ->select('study_class_id', 'attendance_date', DB::raw('count(*) as tracked_count'))
            ->groupBy('study_class_id', 'attendance_date')
            ->get()
            ->keyBy(fn ($row) => $row->study_class_id.'|'.Carbon::parse($row->attendance_date)->toDateString());

        return $sessions->map(function ($session) use ($activeCounts, $trackedCounts): array {
            $date = Carbon::parse($session->session_date)->toDateString();
            $tracked = (int) ($trackedCounts->get($session->study_class_id.'|'.$date)?->tracked_count ?? 0);
            $total = (int) ($activeCounts[$session->study_class_id] ?? 0);

            return [
                'row_key' => $session->study_class_id.'-'.$date,
                'id' => (int) $session->id,
                'class_id' => (int) $session->study_class_id,
                'class_title' => $session->title,
                'session_date' => $date,
                'time' => $session->time_name
                    ?? ($session->scheduled_start && $session->scheduled_end
                        ? Carbon::parse($session->scheduled_start)->format('h:i A').' - '.Carbon::parse($session->scheduled_end)->format('h:i A')
                        : '-'),
                'status' => $session->status,
                'status_label' => ucfirst(str_replace('_', ' ', $session->status)),
                'tracked_count' => $tracked,
                'total_students' => $total,
                'unresolved_count' => max(0, $total - $tracked),
                'request_status' => $session->request_status,
                'request_status_label' => $session->request_status ? ucfirst(str_replace('_', ' ', $session->request_status)) : '-',
                'requested_at' => $session->requested_at ? Carbon::parse($session->requested_at)->format('Y-m-d H:i') : '-',
                'reviewed_at' => $session->reviewed_at ? Carbon::parse($session->reviewed_at)->format('Y-m-d H:i') : '-',
                'completed_at' => $session->completed_at ? Carbon::parse($session->completed_at)->format('Y-m-d H:i') : '-',
            ];
        })->all();
    }
}
