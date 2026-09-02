<?php

namespace App\Modules\Certificate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassCertificateRequest;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    private const TYPES = ['free', 'normal', 'scholarship', 'meal', 'internship'];
    private const PAGE_TYPES = ['free', 'normal', 'scholarship', 'meal', 'internship', 'report'];
    private const SPECIAL_CLASS_KEYWORDS = [
        'free' => ['free'],
        'scholarship' => ['scholar'],
        'internship' => ['internship', 'intership'],
    ];

    public function index(Request $request): Response
    {
        $type = $this->normalisePageType($request->query('type', 'free'));

        return Inertia::render('backend/certificates/Index', [
            'type' => $type,
            'freeCertificates' => fn () => $this->freeCertificates($request),
            'freeCourses' => fn () => $this->customCourses('course_custom'),
            'normalCourses' => fn () => $this->customCourses('course_custom_normal'),
            'generatedIds' => [
                'free' => $this->generateFreeId(),
                'normal' => $this->generateNormalId(),
            ],
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        $type = $this->normaliseType($request->query('type', 'normal'));
        [$track, $month, $year] = $this->classListFilters($request);

        $classes = StudyClass::query()
            ->with([
                'course:id,title,course_track_id',
                'course.track:id,name,sub_category_id',
                'course.track.subCategory:id,name,category_id',
                'course.track.subCategory.category:id,name',
                'teacher:id,name',
                'time:id,time_name',
                'classType:class_type_id,type_name',
            ])
            ->withCount([
                'enrollments as total_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->whereIn('status', ['pre_end', 'ended', 'completed', 'active'])
            ->where(fn (Builder $query) => $this->whereRequestedCertificateClass($query, $type, $month, $year))
            ->when($track !== '' && $track !== 'all', fn (Builder $query) => $query
                ->whereHas('course.track', fn (Builder $trackQuery) => $trackQuery->where('name', $track))
            )
            ->when($type === 'normal', fn (Builder $query) => $this->whereRegularCertificateClass($query))
            ->when($type !== 'normal', fn (Builder $query) => $this->whereTypedCertificateClass($query, $type))
            ->latest('id')
            ->get();

        $requestCounts = DB::table('class_certificate_requests')
            ->where('certificate_type', $type)
            ->whereIn('study_class_id', $classes->pluck('id'))
            ->pluck('student_count', 'study_class_id');

        $printedCounts = DB::table('student_certificate_normal')
            ->select('study_class_id', DB::raw('COUNT(DISTINCT student_id) as printed_students'))
            ->where('certificate_type', $type)
            ->whereIn('study_class_id', $classes->pluck('id'))
            ->groupBy('study_class_id')
            ->pluck('printed_students', 'study_class_id');

        $classes = $classes
            ->map(function (StudyClass $studyClass) use ($printedCounts, $requestCounts): array {
                $requestedStudents = (int) ($requestCounts[$studyClass->id] ?: $studyClass->total_students);
                $printedStudents = (int) ($printedCounts[$studyClass->id] ?? 0);

                return [
                    'id' => $studyClass->id,
                    'category' => $studyClass->course?->track?->name ?? 'Other',
                    'course' => $studyClass->course?->title ?? $studyClass->title,
                    'teacher_name' => $this->instructorDisplayName($studyClass->teacher?->name),
                    'time' => $studyClass->time?->time_name ?? $this->classTime($studyClass),
                    'class_type' => $studyClass->classType?->type_name ?? $studyClass->classTypeValue(),
                    'total_students' => $requestedStudents,
                    'printed_students' => $printedStudents,
                    'remaining_students' => max($requestedStudents - $printedStudents, 0),
                ];
            })
            ->filter(fn (array $studyClass): bool => $studyClass['remaining_students'] > 0)
            ->values();

        return response()->json([
            'data' => $classes,
            'tracks' => $this->certificateTrackOptions(),
        ]);
    }

    public function reportClasses(Request $request): JsonResponse
    {
        [$track, $month, $year] = $this->classListFilters($request);
        $certificateType = $this->normaliseReportType($request->query('certificate_type', 'all'));
        $status = $this->normaliseReportStatus($request->query('status', 'all'));

        $requests = ClassCertificateRequest::query()
            ->with([
                'studyClass' => fn ($query) => $query
                    ->with([
                        'course:id,title,course_track_id',
                        'course.track:id,name,sub_category_id',
                        'teacher:id,name',
                        'time:id,time_name',
                        'classType:class_type_id,type_name',
                    ])
                    ->withCount([
                        'enrollments as total_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
                    ]),
            ])
            ->when($certificateType !== 'all', fn (Builder $query) => $query->where('certificate_type', $certificateType))
            ->when($month !== null, fn (Builder $query) => $query->whereMonth('requested_at', $month))
            ->whereYear('requested_at', $year)
            ->whereHas('studyClass', function (Builder $query) use ($track, $certificateType): void {
                $query->whereIn('status', ['pre_end', 'ended', 'completed', 'active'])
                    ->when($track !== '' && $track !== 'all', fn (Builder $query) => $query
                        ->whereHas('course.track', fn (Builder $trackQuery) => $trackQuery->where('name', $track))
                    )
                    ->when($certificateType !== 'all', fn (Builder $query) => $this->applyCertificateClassTypeFilter($query, $certificateType));
            })
            ->latest('requested_at')
            ->latest('id')
            ->get();

        $printedCounts = DB::table('student_certificate_normal')
            ->select('study_class_id', 'certificate_type', DB::raw('COUNT(DISTINCT student_id) as printed_students'))
            ->whereIn('study_class_id', $requests->pluck('study_class_id'))
            ->whereIn('certificate_type', $requests->pluck('certificate_type'))
            ->groupBy('study_class_id', 'certificate_type')
            ->get()
            ->keyBy(fn ($row): string => $row->study_class_id.'|'.$row->certificate_type);

        $classes = $requests
            ->map(function (ClassCertificateRequest $request) use ($printedCounts): ?array {
                $studyClass = $request->studyClass;

                if (! $studyClass) {
                    return null;
                }

                $requestedStudents = (int) ($request->student_count ?: $studyClass->total_students);
                $printedStudents = (int) ($printedCounts[$studyClass->id.'|'.$request->certificate_type]->printed_students ?? 0);
                $remainingStudents = max($requestedStudents - $printedStudents, 0);
                $printStatus = $requestedStudents > 0 && $remainingStudents === 0 ? 'printed' : 'not_printed';

                return [
                    'id' => $studyClass->id,
                    'category' => $studyClass->course?->track?->name ?? 'Other',
                    'certificate_type' => $request->certificate_type,
                    'course' => $studyClass->course?->title ?? $studyClass->title,
                    'teacher_name' => $this->instructorDisplayName($studyClass->teacher?->name),
                    'time' => $studyClass->time?->time_name ?? $this->classTime($studyClass),
                    'class_type' => $studyClass->classType?->type_name ?? $studyClass->classTypeValue(),
                    'requested_at' => $request->requested_at?->format('Y-m-d h:i A') ?? '-',
                    'total_students' => $requestedStudents,
                    'printed_students' => $printedStudents,
                    'remaining_students' => $remainingStudents,
                    'print_status' => $printStatus,
                ];
            })
            ->filter()
            ->when($status !== 'all', fn ($classes) => $classes->filter(fn (array $class): bool => $class['print_status'] === $status))
            ->values();

        return response()->json([
            'data' => $classes,
            'tracks' => $this->certificateTrackOptions(),
        ]);
    }

    public function students(Request $request, StudyClass $studyClass): JsonResponse
    {
        $type = $this->normaliseType($request->query('type', 'normal'));
        $certificateRequest = ClassCertificateRequest::query()
            ->where('study_class_id', $studyClass->id)
            ->where('certificate_type', $type)
            ->first(['requested_student_ids']);

        $requestedStudentIds = collect($certificateRequest?->requested_student_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values();

        $students = StudentEnrollment::query()
            ->with([
                'student:id,full_name,gender,phone',
                'student.certificates' => fn ($query) => $query
                    ->where('study_class_id', $studyClass->id)
                    ->where('certificate_type', $type),
            ])
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active')
            ->when($requestedStudentIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('student_id', $requestedStudentIds))
            ->orderBy('id')
            ->get()
            ->map(fn (StudentEnrollment $enrollment): array => [
                'id' => $enrollment->student?->id,
                'name' => $enrollment->student?->full_name ?? '-',
                'gender' => $enrollment->student?->gender ?? '-',
                'tel' => $enrollment->student?->phone ?? '-',
                'is_printed' => $enrollment->student?->certificates?->isNotEmpty() ?? false,
            ])
            ->filter(fn (array $student): bool => $student['id'] !== null)
            ->values();

        return response()->json(['data' => $students]);
    }

    public function storeFree(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:100'],
            'course' => ['required', 'string', 'max:100'],
            'end_date' => ['required', 'date'],
        ]);

        DB::table('course_custom')->updateOrInsert(
            ['course_name' => trim($validated['course'])],
            ['updated_at' => now(), 'created_at' => now()]
        );

        DB::table('certificate_class_free')->insert([
            'student_name' => strtoupper(trim($validated['student_name'])),
            'course' => trim($validated['course']),
            'end_date' => $validated['end_date'],
            'certificate_code' => $this->generateFreeId(),
            'status' => 'done',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Certificate saved successfully.');
    }

    public function storePrinted(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'study_class_id' => ['required', 'integer', 'exists:study_classes,id'],
            'certificate_type' => ['required', Rule::in(self::TYPES)],
            'student_name' => ['required', 'string', 'max:100'],
            'course' => ['required', 'string', 'max:100'],
            'granted_date' => ['required', 'string', 'max:50'],
            'certificate_id' => ['required', 'string', 'max:50'],
        ]);

        DB::table('student_certificate_normal')->updateOrInsert(
            [
                'student_id' => $validated['student_id'],
                'study_class_id' => $validated['study_class_id'],
                'certificate_type' => $validated['certificate_type'],
            ],
            [
                'student_name' => $validated['student_name'],
                'course' => $validated['course'],
                'granted_date' => $validated['granted_date'],
                'certificate_id' => $validated['certificate_id'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'id' => $this->generateNormalId()]);
    }

    public function saveCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_name' => ['required', 'string', 'min:2', 'max:100'],
            'scope' => ['nullable', Rule::in(['free', 'normal'])],
        ]);

        DB::table(($validated['scope'] ?? 'normal') === 'free' ? 'course_custom' : 'course_custom_normal')
            ->updateOrInsert(
                ['course_name' => trim($validated['course_name'])],
                ['updated_at' => now(), 'created_at' => now()]
            );

        return response()->json(['success' => true]);
    }

    public function deleteCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_name' => ['required', 'string', 'min:2', 'max:100'],
            'scope' => ['nullable', Rule::in(['free', 'normal'])],
        ]);

        DB::table(($validated['scope'] ?? 'normal') === 'free' ? 'course_custom' : 'course_custom_normal')
            ->where('course_name', trim($validated['course_name']))
            ->delete();

        return response()->json(['success' => true]);
    }

    public function generateId(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'normal');

        return response()->json([
            'id' => $scope === 'free' ? $this->generateFreeId() : $this->generateNormalId(),
        ]);
    }

    private function freeCertificates(Request $request): array
    {
        $course = trim($request->query('course_filter', ''));
        $query = DB::table('certificate_class_free')
            ->when($course !== '', fn ($query) => $query->where('course', $course))
            ->latest('created_at');

        $paginator = $query->paginate(5)->withQueryString();

        return [
            'data' => $paginator->items(),
            'links' => $paginator->linkCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'course_filter' => $course,
        ];
    }

    private function customCourses(string $table): array
    {
        return DB::table($table)->select('course_name')->orderBy('course_name')->get()->all();
    }

    private function applyCertificateClassTypeFilter(Builder $query, string $type): void
    {
        if ($type === 'normal') {
            $this->whereRegularCertificateClass($query);

            return;
        }

        $this->whereTypedCertificateClass($query, $type);
    }

    private function generateFreeId(): string
    {
        return $this->generateMonthlyId('certificate_class_free', 'certificate_code');
    }

    private function generateNormalId(): string
    {
        return $this->generateMonthlyId('student_certificate_normal', 'certificate_id');
    }

    private function generateMonthlyId(string $table, string $column): string
    {
        $prefix = now()->format('ym');
        $last = DB::table($table)
            ->where($column, 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if (is_string($last) && preg_match('/^\d{4}(\d{3,})\s*ETEC$/i', trim($last), $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT).' ETEC';
    }

    private function classListFilters(Request $request): array
    {
        $track = trim((string) $request->query('track', 'all'));
        $now = now();
        $month = $request->query('month', 'all');
        $year = (int) $request->query('year', $now->year);
        $month = $month === 'all' ? null : (int) $month;

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        if ($year < 2018 || $year > (int) $now->year) {
            $year = (int) $now->year;
        }

        return [$track, $month, $year];
    }

    private function instructorDisplayName(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'No instructor';
        }

        return trim(preg_split('/\s*[·•]\s*/u', $name, 2)[0] ?? $name) ?: $name;
    }

    private function normalisePageType(string $type): string
    {
        return in_array($type, self::PAGE_TYPES, true) ? $type : 'free';
    }

    private function normaliseReportType(string $type): string
    {
        return $type === 'all' || in_array($type, self::TYPES, true) ? $type : 'all';
    }

    private function normaliseReportStatus(string $status): string
    {
        return in_array($status, ['all', 'printed', 'not_printed'], true) ? $status : 'all';
    }

    private function whereRegularCertificateClass(Builder $query): Builder
    {
        return $query->where(function (Builder $regular): void {
            foreach (self::SPECIAL_CLASS_KEYWORDS as $keywords) {
                $this->whereNotMatchingClassKeywords($regular, $keywords);
            }
        });
    }

    private function whereTypedCertificateClass(Builder $query, string $type): Builder
    {
        $keywords = match ($type) {
            'meal' => self::SPECIAL_CLASS_KEYWORDS['internship'],
            default => self::SPECIAL_CLASS_KEYWORDS[$type] ?? [$type],
        };

        return $query->where(fn (Builder $query) => $this->whereMatchingClassKeywords($query, $keywords));
    }

    private function whereRequestedCertificateClass(Builder $query, string $type, ?int $month, int $year): Builder
    {
        return $query->whereHas('certificateRequests', fn (Builder $request) => $request
            ->where('certificate_type', $type)
            ->where('status', 'pending')
            ->when($month !== null, fn (Builder $request) => $request->whereMonth('requested_at', $month))
            ->whereYear('requested_at', $year)
        );
    }

    private function certificateTrackOptions(): array
    {
        return DB::table('course_tracks')
            ->where('status', 'active')
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    private function whereMatchingClassKeywords(Builder $query, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            $query
                ->orWhereHas('classType', fn (Builder $classType) => $classType
                    ->where('type_name', 'like', "%{$keyword}%")
                )
                ->orWhereHas('course', fn (Builder $course) => $course
                    ->where('title', 'like', "%{$keyword}%")
                )
                ->orWhereHas('course.track', fn (Builder $track) => $track
                    ->where('name', 'like', "%{$keyword}%")
                )
                ->orWhereHas('course.track.subCategory', fn (Builder $subCategory) => $subCategory
                    ->where('name', 'like', "%{$keyword}%")
                )
                ->orWhereHas('course.track.subCategory.category', fn (Builder $category) => $category
                    ->where('name', 'like', "%{$keyword}%")
                );
        }
    }

    private function whereNotMatchingClassKeywords(Builder $query, array $keywords): void
    {
        $query
            ->whereDoesntHave('classType', fn (Builder $classType) => $this->whereNameMatchesKeywords($classType, 'type_name', $keywords))
            ->whereDoesntHave('course', fn (Builder $course) => $this->whereNameMatchesKeywords($course, 'title', $keywords))
            ->whereDoesntHave('course.track', fn (Builder $track) => $this->whereNameMatchesKeywords($track, 'name', $keywords))
            ->whereDoesntHave('course.track.subCategory', fn (Builder $subCategory) => $this->whereNameMatchesKeywords($subCategory, 'name', $keywords))
            ->whereDoesntHave('course.track.subCategory.category', fn (Builder $category) => $this->whereNameMatchesKeywords($category, 'name', $keywords));
    }

    private function whereNameMatchesKeywords(Builder $query, string $column, array $keywords): void
    {
        $query->where(function (Builder $query) use ($column, $keywords): void {
            foreach ($keywords as $keyword) {
                $query->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    private function normaliseType(string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : 'free';
    }

    private function classTime(StudyClass $studyClass): string
    {
        $start = $studyClass->scheduleStartTime();
        $end = $studyClass->scheduleEndTime();

        return $start && $end ? substr($start, 0, 5).' - '.substr($end, 0, 5) : '-';
    }
}
