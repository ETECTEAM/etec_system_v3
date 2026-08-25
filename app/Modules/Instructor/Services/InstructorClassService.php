<?php

namespace App\Modules\Instructor\Services;

use App\Models\ClassSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class InstructorClassService
{
    // 'on_leave' is system-written (auto-record / official-leave approval) but also
    // selectable here so an instructor tracking attendance manually can record the
    // office-approved leave day without marking the student absent or burning permission.
    public const ATTENDANCE_STATUSES = ['absent', 'present', 'permission', 'on_leave'];
    public const ATTENDANCE_WINDOW_REASON_NO_SESSION = 'no_session';
    public const ATTENDANCE_WINDOW_REASON_BEFORE_START = 'before_start';
    public const ATTENDANCE_WINDOW_REASON_AFTER_DEADLINE = 'after_deadline';
    public const ATTENDANCE_WINDOW_REASON_ALREADY_SUBMITTED = 'already_submitted';
    private const VISIBLE_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    private ?array $termLabels = null;

    public function formOptions(): array
    {
        return [
            'courses' => DB::table('courses')->select('id', 'title')->get(),
            'lessons' => DB::table('course_lessons')->select('id', 'title')->get(),
            'terms' => DB::table('terms')->select('id', 'term_name')->get(),
            'times' => DB::table('times')->select('id', 'time_name')->get(),
            'rooms' => DB::table('rooms')->select('id', 'room_number')->get(),
            'classTypes' => DB::table('class_type')->select('class_type_id', 'type_name')->get(),
        ];
    }

    public function createClass(User $instructor, array $data): int
    {
        $now = now();

        return DB::table('study_classes')->insertGetId([
            'title' => $data['title'],
            'course_id' => $data['course_id'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'term_id' => $data['term_id'] ?? null,
            'time_id' => $data['time_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'class_type_id' => $data['class_type_id'] ?? null,
            'capacity' => $data['capacity'] ?? 20,
            'status' => $data['status'] ?? 'upcoming',
            'teacher_id' => $instructor->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function classes(User $instructor): Collection
    {
        return $this->classesQuery($instructor)
            ->orderByDesc('study_classes.id')
            ->get()
            ->map(fn (stdClass $class) => $this->presentClass($class));
    }

    public function summary(User $instructor): array
    {
        $activeEnrollments = DB::table('student_enrollments')
            ->join('study_classes', 'study_classes.id', '=', 'student_enrollments.study_class_id')
            ->where('student_enrollments.enrollment_status', 'active')
            ->where('study_classes.teacher_id', $instructor->id);

        return [
            'total_classes' => $this->classesQuery($instructor)->count(),
            'total_students' => (clone $activeEnrollments)->count(),
            'male_students' => (clone $activeEnrollments)
                ->join('students', 'students.id', '=', 'student_enrollments.student_id')
                ->where('students.gender', 'male')
                ->count(),
            'female_students' => (clone $activeEnrollments)
                ->join('students', 'students.id', '=', 'student_enrollments.student_id')
                ->where('students.gender', 'female')
                ->count(),
        ];
    }

    public function findForInstructor(User $instructor, int $studyClassId): stdClass
    {
        $class = $this->classesQuery($instructor)
            ->where('study_classes.id', $studyClassId)
            ->first();

        abort_unless($class, 403);

        return $class;
    }

    public function students(int $studyClassId): Collection
    {
        $attendanceStats = $this->attendanceStats($studyClassId);
        $todayAttendance = $this->todayAttendance($studyClassId);
        $officialLeavesToday = $this->officialLeavesToday($studyClassId);

        return DB::table('student_enrollments')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->leftJoin('users as students_user', 'students_user.id', '=', 'students.user_id')
            ->leftJoin('student_scores', 'student_scores.student_enrollment_id', '=', 'student_enrollments.id')
            ->where('student_enrollments.study_class_id', $studyClassId)
            ->where('student_enrollments.enrollment_status', 'active')
            ->orderBy('student_enrollments.id')
            ->select([
                'student_enrollments.id as enrollment_id',
                'students.id',
                'students.full_name',
                'students_user.email',
                'students.gender',
                'students.phone',
                'students.date_of_birth',
                'student_scores.attendance_score',
                'student_scores.activity_score',
                'student_scores.exam_score',
            ])
            ->get()
            ->map(fn (stdClass $student, int $index) => $this->presentStudent(
                $student,
                $index + 1,
                $attendanceStats->get($student->id),
                $todayAttendance->get($student->id),
                $officialLeavesToday->get($student->id),
            ));
    }

    public function pendingRegistrations(int $studyClassId): Collection
    {
        return DB::table('student_enrollments')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.study_class_id', $studyClassId)
            ->where('student_enrollments.enrollment_status', 'pending')
            ->where('student_enrollments.source', 'qr_code')
            ->orderByDesc('student_enrollments.id')
            ->select([
                'student_enrollments.id as enrollment_id',
                'students.id',
                'students.full_name',
                'students.gender',
                'students.phone',
                'student_enrollments.created_at as requested_at',
            ])
            ->get()
            ->map(fn (stdClass $student, int $index) => [
                'roster_no' => $index + 1,
                'enrollment_id' => $student->enrollment_id,
                'id' => $student->id,
                'name' => $student->full_name,
                'gender' => $student->gender,
                'phone' => $student->phone,
                'requested_at' => Carbon::parse($student->requested_at)->format('Y-m-d h:i A'),
            ]);
    }

    public function saveAttendance(User $instructor, int $studyClassId, array $data): void
    {
        $attendanceDate = Carbon::parse($data['attendance_date'] ?? now())->toDateString();

        $enrollments = DB::table('student_enrollments')
            ->where('study_class_id', $studyClassId)
            ->where('enrollment_status', 'active')
            ->get(['id', 'student_id'])
            ->keyBy('id');

        DB::transaction(function () use ($data, $attendanceDate, $enrollments, $instructor, $studyClassId) {
            // Locked here, not just checked before the transaction: closes the same race
            // the auto-record scheduler locks against (AutoRecordSession) — an instructor
            // submitting right as the grace period elapses and the scheduler fires for
            // the same session. Whichever gets the row lock first wins; the other either
            // sees 'pending' still (proceeds normally) or 'auto_recorded' (rejected below,
            // sent to the override flow so every post-auto-record edit goes through one
            // audited path instead of two).
            $session = ClassSession::query()
                ->where('study_class_id', $studyClassId)
                ->whereDate('session_date', $attendanceDate)
                ->lockForUpdate()
                ->first();

            if (! $session || $session->status !== ClassSession::STATUS_PENDING) {
                if ($session && $session->status === ClassSession::STATUS_AUTO_RECORDED) {
                    throw ValidationException::withMessages([
                        'records' => 'The system already auto-recorded this class. Use the override option on the attendance page to correct it.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'records' => 'Attendance can only be tracked during the scheduled class window.',
                ]);
            }

            $this->assertAttendanceWindowOpen($session);

            // Official leave overrides everything: an approved leave covering the
            // attendance date locks the student's row — "absent" is never allowed.
            $blockedNames = DB::table('official_leaves')
                ->whereIn('student_id', $enrollments->keys())
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $attendanceDate)
                ->whereDate('end_date', '>=', $attendanceDate)
                ->pluck('student_id')
                ->filter(function ($studentId) use ($data) {
                    $record = collect($data['records'])
                        ->first(fn ($record) => (int) $record['student_id'] === (int) $studentId);

                    return $record !== null && ($record['status'] ?? '') === 'absent';
                })
                ->map(fn ($studentId) => Student::query()->find($studentId)?->full_name ?? "#{$studentId}")
                ->all();

            if ($blockedNames !== []) {
                throw ValidationException::withMessages([
                    'records' => 'Official leave approved for '.implode(', ', array_slice($blockedNames, 0, 3)).' — these students cannot be marked absent.',
                ]);
            }

            foreach ($data['records'] as $record) {
                $enrollmentId = (int) $record['enrollment_id'];
                $studentId = (int) $record['student_id'];
                $enrollment = $enrollments->get($enrollmentId);

                if (! $enrollment || (int) $enrollment->student_id !== $studentId) {
                    throw ValidationException::withMessages([
                        'records' => 'Attendance can only be saved for active students in this class.',
                    ]);
                }

                DB::table('student_attendances')->updateOrInsert(
                    [
                        'study_class_id' => $studyClassId,
                        'student_enrollment_id' => $enrollmentId,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'student_id' => $studentId,
                        'tracked_by' => $instructor->id,
                        'status' => $record['status'],
                        'source' => StudentAttendance::SOURCE_MANUAL,
                        'note' => $record['note'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            if ($session && $session->status === ClassSession::STATUS_PENDING) {
                $session->update(['status' => ClassSession::STATUS_RECORDED, 'recorded_at' => now()]);
            }
        });
    }

    public function attendanceWindow(int $studyClassId, Carbon|string|null $attendanceDate = null): array
    {
        $date = Carbon::parse($attendanceDate ?? Carbon::today('Asia/Phnom_Penh'))->toDateString();
        $session = ClassSession::query()
            ->where('study_class_id', $studyClassId)
            ->whereDate('session_date', $date)
            ->first();

        if (! $session) {
            return [
                'session_date' => $date,
                'status' => null,
                'can_submit' => false,
                'reason' => self::ATTENDANCE_WINDOW_REASON_NO_SESSION,
                'starts_at' => null,
                'ends_at' => null,
            ];
        }

        $window = $this->windowForSession($session);

        $hasAttendance = $this->hasAttendanceForDate($studyClassId, $date);

        return [
            'session_date' => $session->session_date->toDateString(),
            'status' => $session->status,
            'can_submit' => $session->status === ClassSession::STATUS_PENDING
                && $window['now']->greaterThanOrEqualTo($window['starts_at'])
                && $window['now']->lessThanOrEqualTo($window['ends_at']),
            'reason' => $hasAttendance
                ? self::ATTENDANCE_WINDOW_REASON_ALREADY_SUBMITTED
                : ($window['now']->lessThan($window['starts_at'])
                    ? self::ATTENDANCE_WINDOW_REASON_BEFORE_START
                    : ($window['now']->greaterThan($window['ends_at'])
                        ? self::ATTENDANCE_WINDOW_REASON_AFTER_DEADLINE
                        : null)),
            'starts_at' => $window['starts_at']->format('Y-m-d H:i'),
            'ends_at' => $window['ends_at']->format('Y-m-d H:i'),
        ];
    }

    private function assertAttendanceWindowOpen(ClassSession $session): void
    {
        $window = $this->windowForSession($session);

        if ($window['now']->lessThan($window['starts_at']) || $window['now']->greaterThan($window['ends_at'])) {
            throw ValidationException::withMessages([
                'records' => 'Attendance can only be tracked from the class start time until the configured grace period ends.',
            ]);
        }
    }

    private function windowForSession(ClassSession $session): array
    {
        $graceMinutes = (int) setting('attendance.auto_record_grace_minutes', 15);
        $startsAt = $session->scheduled_start->copy();
        $endsAt = $session->scheduled_start->copy()->addMinutes($graceMinutes);

        return [
            'now' => Carbon::now('Asia/Phnom_Penh'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
    }

    public function hasAttendanceForDate(int $studyClassId, Carbon|string $attendanceDate): bool
    {
        return DB::table('student_attendances')
            ->where('study_class_id', $studyClassId)
            ->whereDate('attendance_date', Carbon::parse($attendanceDate)->toDateString())
            ->exists();
    }

    public function studentAttendanceDetail(int $studyClassId, int $studentId): array
    {
        $student = DB::table('student_enrollments')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->leftJoin('users as students_user', 'students_user.id', '=', 'students.user_id')
            ->leftJoin('student_scores', 'student_scores.student_enrollment_id', '=', 'student_enrollments.id')
            ->where('student_enrollments.study_class_id', $studyClassId)
            ->where('student_enrollments.student_id', $studentId)
            ->where('student_enrollments.enrollment_status', 'active')
            ->select([
                'student_enrollments.id as enrollment_id',
                'students.id',
                'students.full_name',
                'students_user.email',
                'students.gender',
                'students.phone',
                'students.date_of_birth',
                'student_scores.attendance_score',
                'student_scores.activity_score',
                'student_scores.exam_score',
            ])
            ->first();

        abort_unless($student, 404);

        $records = DB::table('student_attendances')
            ->leftJoin('users as trackers', 'trackers.id', '=', 'student_attendances.tracked_by')
            ->where('student_attendances.study_class_id', $studyClassId)
            ->where('student_attendances.student_id', $studentId)
            ->orderByDesc('student_attendances.attendance_date')
            ->select([
                'student_attendances.attendance_date',
                'student_attendances.status',
                'student_attendances.note',
                'student_attendances.updated_at',
                'trackers.name as tracked_by_name',
            ])
            ->get()
            ->map(fn (stdClass $record) => [
                'date' => Carbon::parse($record->attendance_date)->format('Y-m-d'),
                'status' => $record->status,
                'note' => $record->note ?? '-',
                'tracked_by' => $record->tracked_by_name ?? '-',
                'updated_at' => $record->updated_at ? Carbon::parse($record->updated_at)->format('Y-m-d H:i') : '-',
            ]);

        $stats = $this->attendanceStats($studyClassId)->get($studentId);

        return [
            ...$this->presentStudent($student, 1, $stats),
            'records' => $records,
        ];
    }

    public function presentClass(stdClass $class): array
    {
        $studyDays = $this->parseTermDays($class->term_name);
        $timeRange = $this->parseTimeRange($class->time_name);
        $classTypeValue = $this->classTypeValue($class->class_type_name);
        $classTypeLabel = $class->class_type_name
            ?? ($classTypeValue === 'online' ? 'Online Class' : 'Physical Class');

        return [
            'id' => $class->id,
            'title' => $class->title,
            'course' => $class->course_title,
            'lesson' => $class->lesson_title ?? 'No lesson',
            'teacher' => $class->teacher_name ?? '-',
            'building' => $class->building_name ?? '-',
            'floor' => $class->floor_name ?? '-',
            'room' => $class->room_number ?? ($classTypeValue === 'online' ? 'Online' : '-'),
            'status' => $classTypeLabel,
            'class_status' => $class->class_status,
            'class_status_label' => str_replace('_', ' ', ucfirst($class->class_status)),
            'class_type' => $classTypeValue,
            'class_type_label' => $classTypeLabel,
            'term' => $this->termLabel($studyDays),
            'time' => ($timeRange['start'] ?? '-').' - '.($timeRange['end'] ?? '-'),
            // Set when the class is shared and this instructor teaches a named part of
            // it, e.g. "Network" on a Basic IT class split with another instructor.
            'subject' => $class->my_subject ?? null,
            'is_owner' => (bool) ($class->is_owner ?? true),
            'is_shared' => ! empty($class->co_instructor_names),
            'shared_with' => $class->co_instructor_names ?? null,
            'capacity' => (int) $class->capacity,
            'students' => (int) $class->current_students,
            'created_date' => $class->created_at ? Carbon::parse($class->created_at)->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * A class belongs to an instructor when it is assigned to them, or when it has been
     * shared with them ("Collapse Class"). A shared class shows that instructor their own
     * term/time — their half of the week — rather than the class-wide schedule.
     */
    private function classesQuery(User $instructor)
    {
        $activeStudentCounts = DB::table('student_enrollments')
            ->select('study_class_id', DB::raw('count(*) as current_students'))
            ->where('enrollment_status', 'active')
            ->groupBy('study_class_id');

        // The other instructor(s) sharing a class with this one — empty for an unshared
        // class, since it has no study_class_instructors rows at all. Lets the card show
        // "Shared with <name>" rather than leaving a Collapse Class share invisible.
        $coInstructors = DB::table('study_class_instructors')
            ->join('users', 'users.id', '=', 'study_class_instructors.user_id')
            ->where('study_class_instructors.user_id', '!=', $instructor->id)
            ->groupBy('study_class_instructors.study_class_id')
            ->select([
                'study_class_instructors.study_class_id',
                DB::raw("group_concat(users.name separator ', ') as co_instructor_names"),
            ]);

        return DB::table('study_classes')
            ->leftJoinSub($coInstructors, 'co_instructors', function ($join) {
                $join->on('co_instructors.study_class_id', '=', 'study_classes.id');
            })
            ->leftJoin('study_class_instructors as my_slot', function ($join) use ($instructor) {
                $join->on('my_slot.study_class_id', '=', 'study_classes.id')
                    ->where('my_slot.user_id', '=', $instructor->id);
            })
            ->leftJoin('terms as my_terms', 'my_terms.id', '=', 'my_slot.term_id')
            ->leftJoin('times as my_times', 'my_times.id', '=', 'my_slot.time_id')
            ->where(function ($query) use ($instructor) {
                $query->where('study_classes.teacher_id', $instructor->id)
                    ->orWhereNotNull('my_slot.id');
            })
            ->whereIn('study_classes.status', self::VISIBLE_CLASS_STATUSES)
            ->leftJoin('courses', 'courses.id', '=', 'study_classes.course_id')
            ->leftJoin('course_lessons', 'course_lessons.id', '=', 'study_classes.lesson_id')
            ->leftJoin('users as teachers', 'teachers.id', '=', 'study_classes.teacher_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'study_classes.room_id')
            ->leftJoin('floors', 'floors.id', '=', 'rooms.floor_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'floors.building_id')
            ->leftJoin('class_type', 'class_type.class_type_id', '=', 'study_classes.class_type_id')
            ->leftJoin('terms', 'terms.id', '=', 'study_classes.term_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->leftJoinSub($activeStudentCounts, 'active_student_counts', function ($join) {
                $join->on('active_student_counts.study_class_id', '=', 'study_classes.id');
            })
            ->select([
                'study_classes.id',
                'study_classes.title',
                'study_classes.capacity',
                'study_classes.status as class_status',
                'study_classes.created_at',
                'courses.title as course_title',
                'course_lessons.title as lesson_title',
                'teachers.name as teacher_name',
                'rooms.room_number',
                'floors.name as floor_name',
                'buildings.name as building_name',
                'class_type.type_name as class_type_name',
                // A shared class shows this instructor their own days/time; unshared
                // classes fall back to the class-wide schedule.
                DB::raw('coalesce(my_terms.term_name, terms.term_name) as term_name'),
                DB::raw('coalesce(my_times.time_name, times.time_name) as time_name'),
                'my_slot.subject as my_subject',
                'co_instructors.co_instructor_names',
                DB::raw('coalesce(active_student_counts.current_students, 0) as current_students'),
            ])
            // Appended after select(), which would otherwise reset the column list. Shared
            // instructors teach the class but don't own it, so the card can hide the actions
            // (edit, end, share) the backend would reject for them anyway.
            ->selectRaw('(study_classes.teacher_id = ?) as is_owner', [$instructor->id]);
    }

    private function attendanceStats(int $studyClassId): Collection
    {
        return DB::table('student_attendances')
            ->where('study_class_id', $studyClassId)
            ->select([
                'student_id',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'present' then 1 else 0 end) as present"),
                DB::raw("sum(case when status = 'permission' then 1 else 0 end) as permission_count"),
                DB::raw("sum(case when status = 'absent' then 1 else 0 end) as absent"),
                DB::raw("sum(case when status = 'on_leave' then 1 else 0 end) as on_leave_count"),
            ])
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');
    }

    private function todayAttendance(int $studyClassId): Collection
    {
        return DB::table('student_attendances')
            ->where('study_class_id', $studyClassId)
            ->whereDate('attendance_date', Carbon::today('Asia/Phnom_Penh'))
            ->get(['student_id', 'status', 'note'])
            ->keyBy('student_id');
    }

    private function presentStudent(stdClass $student, int $rosterNo, ?stdClass $attendanceStats = null, ?stdClass $todayAttendance = null, ?object $officialLeave = null): array
    {
        return [
            'id' => $student->id,
            'roster_no' => $rosterNo,
            'enrollment_id' => $student->enrollment_id,
            'name' => $student->full_name ?? '-',
            'email' => $student->email ?? '-',
            'gender' => $student->gender ?? '-',
            'phone' => $student->phone ?? '-',
            'date_of_birth' => $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('Y-m-d') : null,
            'on_leave' => $officialLeave !== null,
            'on_leave_range' => $officialLeave
                ? Carbon::parse($officialLeave->start_date)->format('M j').' - '.Carbon::parse($officialLeave->end_date)->format('M j')
                : null,
            'attendance' => [
                'total' => (int) ($attendanceStats->total ?? 0),
                'present' => (int) ($attendanceStats->present ?? 0),
                'permission' => (int) ($attendanceStats->permission_count ?? 0),
                'absent' => (int) ($attendanceStats->absent ?? 0),
                'on_leave' => (int) ($attendanceStats->on_leave_count ?? 0),
                'current_status' => $todayAttendance->status ?? ($officialLeave ? 'on_leave' : 'absent'),
                'note' => $todayAttendance->note ?? '',
            ],
            'scores' => [
                'attendance' => (float) ($student->attendance_score ?? 0),
                'activity' => (float) ($student->activity_score ?? 0),
                'exam' => (float) ($student->exam_score ?? 0),
            ],
        ];
    }

    /**
     * Approved official leaves covering today for this class's students — the source
     * of the "On Leave" lock in the attendance UI and saveAttendance's absent guard.
     */
    private function officialLeavesToday(int $studyClassId): Collection
    {
        $today = Carbon::today('Asia/Phnom_Penh')->toDateString();

        return DB::table('official_leaves')
            ->whereIn('student_id', fn ($query) => $query
                ->select('se.student_id')
                ->from('student_enrollments as se')
                ->whereColumn('se.study_class_id', $studyClassId)
                ->where('se.enrollment_status', 'active'))
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get(['student_id', 'start_date', 'end_date'])
            ->keyBy('student_id');
    }

    public function saveScores(int $studyClassId, array $records): void
    {
        $enrollments = DB::table('student_enrollments')
            ->where('study_class_id', $studyClassId)
            ->where('enrollment_status', 'active')
            ->get(['id', 'student_id'])
            ->keyBy('id');

        DB::transaction(function () use ($records, $enrollments, $studyClassId): void {
            foreach ($records as $record) {
                $enrollmentId = (int) $record['enrollment_id'];
                $studentId = (int) $record['student_id'];
                $enrollment = $enrollments->get($enrollmentId);
                $now = now();

                if (! $enrollment || (int) $enrollment->student_id !== $studentId) {
                    throw ValidationException::withMessages([
                        'scores' => 'Scores can only be saved for active students in this class.',
                    ]);
                }

                $scoreExists = DB::table('student_scores')
                    ->where('student_enrollment_id', $enrollmentId)
                    ->exists();

                $payload = [
                    'study_class_id' => $studyClassId,
                    'student_id' => $studentId,
                    'attendance_score' => $record['attendance_score'],
                    'activity_score' => $record['activity_score'],
                    'exam_score' => $record['exam_score'],
                    'updated_at' => $now,
                ];

                if ($scoreExists) {
                    DB::table('student_scores')
                        ->where('student_enrollment_id', $enrollmentId)
                        ->update($payload);

                    continue;
                }

                DB::table('student_scores')->insert([
                    'student_enrollment_id' => $enrollmentId,
                    'study_class_id' => $studyClassId,
                    'student_id' => $studentId,
                    'attendance_score' => $record['attendance_score'],
                    'activity_score' => $record['activity_score'],
                    'exam_score' => $record['exam_score'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function teamsForClass(int $studyClassId): array
    {
        $teams = DB::table('teams')
            ->where('group_id', $studyClassId)
            ->orderBy('id')
            ->get();

        if ($teams->isEmpty()) {
            return [];
        }

        $members = DB::table('team_members')
            ->join('students', 'students.id', '=', 'team_members.student_id')
            ->whereIn('team_members.team_id', $teams->pluck('id'))
            ->orderBy('team_members.team_id')
            ->orderBy('students.full_name')
            ->select([
                'team_members.team_id',
                'team_members.student_id',
                'students.full_name',
                'students.gender',
            ])
            ->get()
            ->groupBy('team_id');

        return $teams->map(function (stdClass $team) use ($members): array {
            $teamMembers = $members->get($team->id, collect());

            return [
                'id' => $team->id,
                'team_name' => $team->team_name,
                'project_topic' => $team->project_topic,
                'members' => $teamMembers->map(fn (stdClass $member): array => [
                    'id' => (int) $member->student_id,
                    'name' => $member->full_name,
                    'gender' => $member->gender,
                ])->values()->all(),
                'member_ids' => $teamMembers->pluck('student_id')->map(fn ($id): int => (int) $id)->values()->all(),
                'member_count' => $teamMembers->count(),
            ];
        })->values()->all();
    }

    public function saveTeams(int $studyClassId, int $teamCount, array $teams, ?int $createdByUserId = null): array
    {
        $activeStudents = $this->students($studyClassId)->keyBy('id');

        if ($teamCount !== count($teams)) {
            throw ValidationException::withMessages([
                'teams_count' => 'The number of teams must match the generated team count.',
            ]);
        }

        $seenStudentIds = [];

        foreach ($teams as $index => $team) {
            $teamName = trim((string) ($team['team_name'] ?? ''));
            $projectTopic = trim((string) ($team['project_topic'] ?? ''));
            $studentIds = array_values(array_unique(array_map('intval', $team['student_ids'] ?? [])));

            if ($teamName === '') {
                throw ValidationException::withMessages([
                    "teams.$index.team_name" => 'Team name is required.',
                ]);
            }

            if (empty($studentIds)) {
                throw ValidationException::withMessages([
                    "teams.$index.student_ids" => 'Each team must have at least one member.',
                ]);
            }

            foreach ($studentIds as $studentId) {
                if (! $activeStudents->has($studentId)) {
                    throw ValidationException::withMessages([
                        "teams.$index.student_ids" => 'Only students from this class can be assigned to a team.',
                    ]);
                }

                if (isset($seenStudentIds[$studentId])) {
                    throw ValidationException::withMessages([
                        'teams' => 'A student can belong to only one team in this group.',
                    ]);
                }

                $seenStudentIds[$studentId] = true;
            }
        }

        DB::transaction(function () use ($studyClassId, $teams, $createdByUserId): void {
            DB::table('teams')->where('group_id', $studyClassId)->delete();

            $now = now();

            foreach ($teams as $team) {
                $projectTopic = trim((string) ($team['project_topic'] ?? ''));

                $teamId = DB::table('teams')->insertGetId([
                    'group_id' => $studyClassId,
                    'team_name' => trim((string) $team['team_name']),
                    'project_topic' => $projectTopic !== '' ? $projectTopic : null,
                    'created_by' => $createdByUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (array_values(array_unique(array_map('intval', $team['student_ids'] ?? []))) as $studentId) {
                    DB::table('team_members')->insert([
                        'team_id' => $teamId,
                        'group_id' => $studyClassId,
                        'student_id' => $studentId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        return $this->teamsForClass($studyClassId);
    }

    public function updateStudentProfile(int $studyClassId, int $studentId, array $data): void
    {
        $enrollment = $this->activeEnrollmentForStudent($studyClassId, $studentId);
        $student = $enrollment->student;

        abort_unless($student instanceof Student, 404);

        DB::transaction(function () use ($student, $data): void {
            $student->forceFill([
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'] ?: null,
                'phone' => $data['phone'],
            ])->save();

            if ($student->user) {
                $student->user->forceFill(['name' => $data['full_name']])->save();
            }
        });
    }

    public function transferStudent(int $studyClassId, int $studentId, StudyClass $targetClass): void
    {
        $enrollment = $this->activeEnrollmentForStudent($studyClassId, $studentId);

        if ($targetClass->id === $studyClassId) {
            throw ValidationException::withMessages([
                'study_class_id' => 'This student is already in that class.',
            ]);
        }

        $alreadyEnrolledInTarget = StudentEnrollment::query()
            ->where('study_class_id', $targetClass->id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadyEnrolledInTarget) {
            throw ValidationException::withMessages([
                'study_class_id' => 'This student is already enrolled in the target class.',
            ]);
        }

        $targetHasSeat = StudentEnrollment::query()
            ->where('study_class_id', $targetClass->id)
            ->where('enrollment_status', 'active')
            ->count() < (int) $targetClass->capacity;

        if (! $targetHasSeat) {
            throw ValidationException::withMessages([
                'study_class_id' => 'This class is full.',
            ]);
        }

        DB::transaction(function () use ($enrollment, $studentId, $targetClass): void {
            $enrollment->update([
                'study_class_id' => $targetClass->id,
                'updated_at' => now(),
            ]);

            DB::table('student_attendances')
                ->where('student_enrollment_id', $enrollment->id)
                ->where('student_id', $studentId)
                ->update([
                    'study_class_id' => $targetClass->id,
                    'updated_at' => now(),
                ]);

            DB::table('student_scores')
                ->where('student_enrollment_id', $enrollment->id)
                ->update([
                    'study_class_id' => $targetClass->id,
                    'updated_at' => now(),
                ]);
        });
    }

    private function activeEnrollmentForStudent(int $studyClassId, int $studentId): StudentEnrollment
    {
        $enrollment = StudentEnrollment::query()
            ->with(['student.user'])
            ->where('study_class_id', $studyClassId)
            ->where('student_id', $studentId)
            ->where('enrollment_status', 'active')
            ->first();

        abort_unless($enrollment, 404);

        return $enrollment;
    }

    private function classTypeValue(?string $classTypeName): string
    {
        return str_contains(strtolower((string) $classTypeName), 'online') ? 'online' : 'physical';
    }

    private function termLabel(array $studyDays): string
    {
        if (! $studyDays) {
            return '-';
        }

        $key = $this->studyDaysKey($studyDays);

        return $this->termLabels()[$key] ?? implode(' & ', $studyDays);
    }

    private function termLabels(): array
    {
        if ($this->termLabels !== null) {
            return $this->termLabels;
        }

        return $this->termLabels = DB::table('terms')
            ->select('term_name')
            ->orderBy('term_name')
            ->get()
            ->mapWithKeys(fn (stdClass $term) => [
                $this->studyDaysKey($this->parseTermDays($term->term_name)) => $term->term_name,
            ])
            ->filter(fn (string $label, string $key) => $key !== '')
            ->all();
    }

    private function parseTermDays(?string $termName): array
    {
        $dayMap = [
            'Mon' => 'Monday', 'Monday' => 'Monday',
            'Tue' => 'Tuesday', 'Tues' => 'Tuesday', 'Tuesday' => 'Tuesday',
            'Wed' => 'Wednesday', 'Wednesday' => 'Wednesday',
            'Thu' => 'Thursday', 'Thur' => 'Thursday', 'Thurs' => 'Thursday', 'Thursday' => 'Thursday',
            'Fri' => 'Friday', 'Friday' => 'Friday',
            'Sat' => 'Saturday', 'Saturday' => 'Saturday',
            'Sun' => 'Sunday', 'Sunday' => 'Sunday',
        ];

        return collect(preg_split('/\s*(?:-|,|&|\/|\+|and)\s*/i', (string) $termName))
            ->map(fn (string $day) => $dayMap[trim($day)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function studyDaysKey(array $studyDays): string
    {
        return collect($studyDays)
            ->map(fn (string $day) => strtolower(trim($day)))
            ->sort()
            ->implode('|');
    }

    private function parseTimeRange(?string $timeName): array
    {
        $range = preg_match('/\(([^)]+)\)/', (string) $timeName, $match)
            ? $match[1]
            : $timeName;
        [$start, $end] = array_pad(preg_split('/\s*-\s*/', trim((string) $range)), 2, null);

        return [
            'start' => $this->toHm($start),
            'end' => $this->toHm($end),
        ];
    }

    private function toHm(?string $time): ?string
    {
        $time = trim((string) $time);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $match)) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            $meridiem = strtoupper($match[3]);

            if ($meridiem === 'AM' && $hour === 12) {
                $hour = 0;
            }
            if ($meridiem === 'PM' && $hour !== 12) {
                $hour += 12;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $match)) {
            return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
        }

        return null;
    }
}
