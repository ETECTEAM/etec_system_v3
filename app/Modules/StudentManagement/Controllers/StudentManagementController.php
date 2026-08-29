<?php

namespace App\Modules\StudentManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\StudentEnrollment;
use App\Models\StudentPermission;
use App\Models\StudyClass;
use App\Models\Time;
use App\Modules\Attendance\Queries\HasApprovedPermission;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\StudentManagement\Requests\GrantPermissionRequest;
use App\Modules\StudentManagement\Requests\MarkLateRequest;
use App\Modules\StudentManagement\Requests\TransferStudentRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentManagementController extends Controller
{
    public function students(Request $request, GetClassList $classes): Response
    {
        $query = $this->studentQuery($request);
        $paginator = $query->paginate(15)->withQueryString();

        $paginator->getCollection()->transform(function (Student $student): array {
            $activeEnrollment = $student->currentEnrollment ?? $student->enrollments->first();
            $studyClass = $activeEnrollment?->studyClass;
            $latestAttendance = $activeEnrollment?->latestAttendance;

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'gender' => $student->gender,
                'phone' => $student->phone,
                'current_enrollment_id' => $activeEnrollment?->id,
                'current_course_id' => $studyClass?->course_id ?? $student->course_id,
                'current_class_id' => $studyClass?->id,
                'current_class_title' => $studyClass?->title,
                'course' => $studyClass?->course?->title ?? $student->course?->title ?? '—',
                'instructor' => $studyClass?->teacher?->name,
                'current_term' => $studyClass?->term?->term_name,
                'current_time' => $studyClass?->time?->time_name,
                'permission_count' => (int) ($student->permission_count ?? 0),
                'latest_attendance' => $latestAttendance ? [
                    'id' => $latestAttendance->id,
                    'status' => $latestAttendance->status,
                    'attendance_date' => $latestAttendance->attendance_date?->toDateString(),
                    'study_class_id' => $latestAttendance->study_class_id,
                    'source' => $latestAttendance->source,
                ] : null,
                'created_at' => $student->created_at?->toIso8601String(),
            ];
        });

        return Inertia::render('backend/student-management/Students', [
            'students' => $paginator,
            'filters' => $request->only([
                'search',
                'course_id',
                'time_id',
            ]),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'times' => Time::query()->orderBy('time_name')->get(['id', 'time_name']),
            'transferClasses' => $classes->forSelect(),
        ]);
    }

    public function locks(Request $request): Response
    {
        $query = $this->lockQuery($request, null);
        $paginator = $query->paginate(15)->withQueryString();
        $paginator->getCollection()->transform(fn (StudentAttendanceBlock $block): array => $this->formatBlock($block));

        $baseQuery = $this->lockQuery($request, null);
        $summary = [
            'absenceApproved' => (clone $baseQuery)
                ->where('block_type', 'soft_lock')
                ->where('status', 'approved')
                ->count(),
            'permissionApproved' => (clone $baseQuery)
                ->where('block_type', 'hard_lock')
                ->whereIn('status', ['approved', 'unlocked'])
                ->count(),
            'totalApproved' => (clone $baseQuery)
                ->whereIn('status', ['approved', 'unlocked'])
                ->count(),
        ];

        return Inertia::render('backend/student-management/Locks', [
            'locks' => $paginator,
            'filters' => $request->only(['search']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'summary' => $summary,
            'settings' => $this->currentLockSettings(),
        ]);
    }

    public function hardLocks(Request $request): Response
    {
        $query = $this->lockQuery($request, 'hard_lock');
        $paginator = $query->paginate(15)->withQueryString();
        $paginator->getCollection()->transform(fn (StudentAttendanceBlock $block): array => $this->formatBlock($block));

        return Inertia::render('backend/student-management/HardLocks', [
            'hardLocks' => $paginator,
            'filters' => $request->only(['search', 'course_id', 'status', 'date_from', 'date_to']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'statusOptions' => [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Rejected', 'value' => 'rejected'],
                ['label' => 'Unlocked', 'value' => 'unlocked'],
            ],
        ]);
    }

    public function grantPermission(GrantPermissionRequest $request, Student $student): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $actorId = (int) $request->user()->id;
        $enrollment = $this->currentEnrollmentForStudent($student->id);
        $studyClass = $enrollment->studyClass()->with(['course:id,title', 'teacher:id,name', 'term:id,term_name', 'time:id,time_name'])->firstOrFail();
        $startDate = Carbon::parse($validated['start_date'])->toDateString();
        $endDate = Carbon::parse($validated['end_date'])->toDateString();
        $note = trim((string) ($validated['note'] ?? '')) ?: null;

        DB::transaction(function () use ($request, $student, $studyClass, $actorId, $validated, $startDate, $endDate, $note): void {
            StudentPermission::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'study_class_id' => $studyClass->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => $validated['reason'],
                    'note' => $note,
                ],
                [
                    'approved_by' => $actorId,
                ],
            );

            $attendances = StudentAttendance::query()
                ->where('student_id', $student->id)
                ->where('study_class_id', $studyClass->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->lockForUpdate()
                ->get();

            foreach ($attendances as $attendance) {
                if (in_array($attendance->status, ['permission', 'present'], true)) {
                    continue;
                }

                $before = $this->attendanceSnapshot($attendance);

                $attendance->forceFill([
                    'status' => 'permission',
                    'tracked_by' => $actorId,
                    'source' => StudentAttendance::SOURCE_ADMIN_EDIT,
                ])->save();

                AttendanceAdjustment::create([
                    'attendance_id' => $attendance->id,
                    'student_id' => $student->id,
                    'changed_by' => $actorId,
                    'action' => 'admin_permission_created',
                    'study_class_id' => $studyClass->id,
                    'previous_status' => $before['status'],
                    'new_status' => 'permission',
                    'reason' => $validated['reason'],
                    'ip_address' => $request->ip(),
                    'effective_date' => $attendance->attendance_date?->toDateString(),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'before_payload' => $before,
                    'after_payload' => $this->attendanceSnapshot($attendance),
                ]);
            }
        });

        return back()->with('success', 'Permission saved successfully.');
    }

    public function transferClass(TransferStudentRequest $request, Student $student): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $actorId = (int) $request->user()->id;
        $enrollment = $this->currentEnrollmentForStudent($student->id);
        $currentClass = $enrollment->studyClass()->with(['course:id,title', 'teacher:id,name', 'term:id,term_name', 'time:id,time_name'])->firstOrFail();
        $targetClass = $this->resolveTransferTargetClass($student->id, $currentClass, (int) $validated['study_class_id'], $enrollment->id);
        $effectiveDate = Carbon::parse($validated['effective_date'])->toDateString();
        $reason = trim((string) ($validated['reason'] ?? ''));

        DB::transaction(function () use ($request, $student, $actorId, $enrollment, $currentClass, $targetClass, $effectiveDate, $reason): void {
            $newEnrollment = StudentEnrollment::create([
                'study_class_id' => $targetClass->id,
                'student_id' => $student->id,
                'course_id' => $targetClass->course_id,
                'term_id' => $targetClass->term_id,
                'time_id' => $targetClass->time_id,
                'enrollment_status' => 'active',
                'payment_status' => $enrollment->payment_status,
                'source' => $enrollment->source,
                'fee_amount' => $enrollment->fee_amount,
                'document_fee_amount' => $enrollment->document_fee_amount,
                'amount_paid' => $enrollment->amount_paid,
                'enrolled_at' => Carbon::parse($effectiveDate)->startOfDay(),
                'paid_at' => $enrollment->paid_at,
                'no_room_and_instructor' => false,
                'no_instructor' => false,
                'no_room' => false,
            ]);

            $enrollment->forceFill([
                'enrollment_status' => 'cancelled',
            ])->save();

            AttendanceAdjustment::create([
                'attendance_id' => null,
                'student_id' => $student->id,
                'changed_by' => $actorId,
                'action' => 'student_class_transferred',
                'study_class_id' => $currentClass->id,
                'target_study_class_id' => $targetClass->id,
                'previous_status' => 'active',
                'new_status' => 'cancelled',
                'reason' => $reason !== '' ? $reason : 'Student transferred to another class.',
                'ip_address' => $request->ip(),
                'effective_date' => $effectiveDate,
                'before_payload' => [
                    'enrollment_id' => $enrollment->id,
                    'study_class_id' => $currentClass->id,
                    'course_id' => $currentClass->course_id,
                    'term_id' => $currentClass->term_id,
                    'time_id' => $currentClass->time_id,
                ],
                'after_payload' => [
                    'enrollment_id' => $newEnrollment->id,
                    'study_class_id' => $targetClass->id,
                    'course_id' => $targetClass->course_id,
                    'term_id' => $targetClass->term_id,
                    'time_id' => $targetClass->time_id,
                ],
            ]);
        });

        return back()->with('success', 'Student transferred successfully.');
    }

    public function markLate(MarkLateRequest $request, Student $student, HasApprovedPermission $hasApprovedPermission): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $actorId = (int) $request->user()->id;
        $enrollment = $this->currentEnrollmentForStudent($student->id);
        $studyClass = $enrollment->studyClass()->with(['course:id,title', 'teacher:id,name', 'term:id,term_name', 'time:id,time_name'])->firstOrFail();
        $attendance = $enrollment->latestAttendance()->lockForUpdate()->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'student' => 'No finalized attendance record was found for this student.',
            ]);
        }

        $attendanceDate = $attendance->attendance_date?->toDateString() ?? Carbon::parse($attendance->created_at ?? now())->toDateString();

        if ($hasApprovedPermission->handle($student->id, $studyClass->id, $attendanceDate)) {
            return back()->with('success', 'Approved permission already covers this attendance.');
        }

        if ($attendance->status === 'late') {
            return back()->with('success', 'Student is already marked late.');
        }

        if ($attendance->status === 'permission') {
            return back()->with('success', 'Student already has permission for this attendance.');
        }

        if ($attendance->status !== 'absent') {
            throw ValidationException::withMessages([
                'status' => 'Only an absent finalized attendance can be marked late.',
            ]);
        }

        DB::transaction(function () use ($request, $student, $actorId, $studyClass, $attendance, $attendanceDate, $validated): void {
            $before = $this->attendanceSnapshot($attendance);

            $attendance->forceFill([
                'status' => 'late',
                'tracked_by' => $actorId,
                'source' => StudentAttendance::SOURCE_ADMIN_EDIT,
            ])->save();

            AttendanceAdjustment::create([
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'changed_by' => $actorId,
                'action' => 'attendance_changed_to_late',
                'study_class_id' => $studyClass->id,
                'previous_status' => $before['status'],
                'new_status' => 'late',
                'reason' => $validated['reason'],
                'ip_address' => $request->ip(),
                'effective_date' => $attendanceDate,
                'before_payload' => $before,
                'after_payload' => $this->attendanceSnapshot($attendance),
            ]);
        });

        return back()->with('success', 'Attendance marked as late successfully.');
    }

    private function studentQuery(Request $request): Builder
    {
        $query = Student::query()
            ->with([
                'course:id,title',
                'currentEnrollment' => fn ($relation) => $relation
                    ->where('enrollment_status', 'active')
                    ->with([
                        'studyClass:id,title,course_id,teacher_id,term_id,time_id,status,capacity',
                        'studyClass.course:id,title',
                        'studyClass.teacher:id,name',
                        'studyClass.term:id,term_name',
                        'studyClass.time:id,time_name',
                        'latestAttendance:id,student_enrollment_id,student_id,study_class_id,attendance_date,status,source,created_at',
                    ]),
            ])
            ->withCount([
                'attendances as permission_count' => fn ($relation) => $relation->where('status', 'permission'),
            ])
            ->orderBy('full_name');

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('student_code', 'like', '%'.$search.'%')
                    ->orWhereHas('enrollments', fn (Builder $relation) => $relation
                        ->whereHas('studyClass', function (Builder $class) use ($search): void {
                            $class->where('title', 'like', '%'.$search.'%')
                                ->orWhereHas('course', fn (Builder $course) => $course->where('title', 'like', '%'.$search.'%'))
                                ->orWhereHas('teacher', fn (Builder $teacher) => $teacher->where('name', 'like', '%'.$search.'%'));
                        }));
            });
        }

        if ($courseId = $request->integer('course_id')) {
            $query->where(function (Builder $builder) use ($courseId): void {
                $builder->where('course_id', $courseId)
                    ->orWhereHas('enrollments', fn (Builder $relation) => $relation
                        ->whereHas('studyClass', fn (Builder $class) => $class->where('course_id', $courseId)));
            });
        }

        if ($timeId = $request->integer('time_id')) {
            $query->where(function (Builder $builder) use ($timeId): void {
                $builder->where('time_id', $timeId)
                    ->orWhereHas('enrollments', fn (Builder $relation) => $relation
                        ->whereHas('studyClass', fn (Builder $class) => $class->where('time_id', $timeId)));
            });
        }

        return $query;
    }

    private function currentEnrollmentForStudent(int $studentId): StudentEnrollment
    {
        $enrollment = StudentEnrollment::query()
            ->with([
                'studyClass:id,title,course_id,teacher_id,term_id,time_id,status,capacity',
                'studyClass.course:id,title',
                'studyClass.teacher:id,name',
                'studyClass.term:id,term_name',
                'studyClass.time:id,time_name',
                'latestAttendance:id,student_enrollment_id,student_id,study_class_id,attendance_date,status,source,created_at',
            ])
            ->where('student_id', $studentId)
            ->where('enrollment_status', 'active')
            ->latest('id')
            ->first();

        abort_unless($enrollment, 404, 'This student does not currently have an active enrollment.');

        return $enrollment;
    }

    private function resolveTransferTargetClass(int $studentId, StudyClass $currentClass, int $targetClassId, int $currentEnrollmentId): StudyClass
    {
        $targetClass = StudyClass::query()
            ->with([
                'course:id,title',
                'teacher:id,name',
                'term:id,term_name',
                'time:id,time_name',
            ])
            ->withCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->findOrFail($targetClassId);

        if ($targetClass->id === $currentClass->id) {
            throw ValidationException::withMessages([
                'study_class_id' => 'This student is already in that class.',
            ]);
        }

        if (! in_array($targetClass->status, ['upcoming', 'active', 'pre_end'], true)) {
            throw ValidationException::withMessages([
                'study_class_id' => 'The selected class is not open for transfer.',
            ]);
        }

        if ($targetClass->course_id !== $currentClass->course_id) {
            throw ValidationException::withMessages([
                'study_class_id' => 'The selected class must belong to the same course.',
            ]);
        }

        if ((int) ($targetClass->current_students ?? 0) >= (int) $targetClass->capacity) {
            throw ValidationException::withMessages([
                'study_class_id' => 'The selected class is full.',
            ]);
        }

        if ($this->studentHasScheduleConflict($studentId, $targetClass, $currentEnrollmentId)) {
            throw ValidationException::withMessages([
                'study_class_id' => 'The selected class conflicts with another active class schedule.',
            ]);
        }

        return $targetClass;
    }

    private function studentHasScheduleConflict(int $studentId, StudyClass $targetClass, int $currentEnrollmentId): bool
    {
        $targetDays = StudyClass::parseTermDays($targetClass->term?->term_name);
        $targetTime = StudyClass::parseTimeRange($targetClass->time?->time_name);

        if ($targetDays === [] || empty($targetTime['start']) || empty($targetTime['end'])) {
            return false;
        }

        $enrollments = StudentEnrollment::query()
            ->with([
                'studyClass:id,title,course_id,teacher_id,term_id,time_id,status,capacity',
                'studyClass.term:id,term_name',
                'studyClass.time:id,time_name',
            ])
            ->where('student_id', $studentId)
            ->where('enrollment_status', 'active')
            ->where('id', '!=', $currentEnrollmentId)
            ->get();

        foreach ($enrollments as $enrollment) {
            $studyClass = $enrollment->studyClass;

            if (! $studyClass) {
                continue;
            }

            $otherDays = StudyClass::parseTermDays($studyClass->term?->term_name);
            $otherTime = StudyClass::parseTimeRange($studyClass->time?->time_name);

            if ($otherDays === [] || empty($otherTime['start']) || empty($otherTime['end'])) {
                continue;
            }

            if (! array_intersect($targetDays, $otherDays)) {
                continue;
            }

            if ($this->timeRangesOverlap($targetTime, $otherTime)) {
                return true;
            }
        }

        return false;
    }

    private function timeRangesOverlap(array $first, array $second): bool
    {
        $firstStart = $this->timeToMinutes($first['start'] ?? null);
        $firstEnd = $this->timeToMinutes($first['end'] ?? null);
        $secondStart = $this->timeToMinutes($second['start'] ?? null);
        $secondEnd = $this->timeToMinutes($second['end'] ?? null);

        if ($firstStart === null || $firstEnd === null || $secondStart === null || $secondEnd === null) {
            return false;
        }

        return $firstStart < $secondEnd && $secondStart < $firstEnd;
    }

    private function timeToMinutes(?string $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }

        [$hour, $minute] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return ($hour * 60) + $minute;
    }

    private function attendanceSnapshot(StudentAttendance $attendance): array
    {
        return [
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'study_class_id' => $attendance->study_class_id,
            'student_enrollment_id' => $attendance->student_enrollment_id,
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'status' => $attendance->status,
            'source' => $attendance->source,
            'note' => $attendance->note,
            'tracked_by' => $attendance->tracked_by,
        ];
    }

    private function lockQuery(Request $request, ?string $blockType): Builder
    {
        $query = StudentAttendanceBlock::query()
            ->with([
                'student' => fn ($relation) => $relation
                    ->select('id', 'full_name', 'phone', 'student_code')
                    ->withCount([
                        'attendances as absence_count' => fn (Builder $count) => $count->where('status', 'absent'),
                        'attendances as permission_count' => fn (Builder $count) => $count->where('status', 'permission'),
                    ]),
                'course:id,title',
                'approvedBy:id,name',
            ])
            ->orderByDesc('blocked_at');

        if ($blockType !== null) {
            $query->where('block_type', $blockType);
        }

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->whereHas('student', function (Builder $relation) use ($search): void {
                $relation->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('student_code', 'like', '%'.$search.'%');
            });
        }

        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        if ($dateFrom = trim((string) $request->input('date_from', ''))) {
            $query->whereDate('blocked_at', '>=', $dateFrom);
        }

        if ($dateTo = trim((string) $request->input('date_to', ''))) {
            $query->whereDate('blocked_at', '<=', $dateTo);
        }

        return $query;
    }

    private function formatBlock(StudentAttendanceBlock $block): array
    {
        $settings = $this->currentLockSettings();

        return [
            'id' => $block->id,
            'student' => [
                'id' => $block->student?->id,
                'full_name' => $block->student?->full_name,
                'student_code' => $block->student?->student_code,
                'phone' => $block->student?->phone,
                'absence_count' => (int) ($block->student?->absence_count ?? 0),
                'permission_count' => (int) ($block->student?->permission_count ?? 0),
            ],
            'course' => $block->course?->title,
            'block_type' => $block->block_type,
            'status' => $block->status,
            'permission_period' => $block->block_type === 'hard_lock' ? 'HARD LOCK' : 'ABSENCE LIMIT',
            'reason' => $block->block_type === 'hard_lock'
                ? 'Hard lock: exceeded the '.$settings['postApprovalAbsenceLimit'].' extra absences after admin approval'
                : 'Reached '.$settings['absenceSoftLockThreshold'].' absences this month',
            'blocked_at' => $block->blocked_at?->toIso8601String(),
            'approved_at' => $block->approved_at?->toIso8601String(),
            'comment' => $block->admin_comment,
            'approved_by' => $block->approvedBy?->name,
        ];
    }

    private function currentLockSettings(): array
    {
        return [
            'absenceSoftLockThreshold' => (int) setting('attendance.absence_soft_lock_threshold', 3),
            'postApprovalAbsenceLimit' => (int) setting('attendance.post_approval_absence_limit', 2),
        ];
    }
}
