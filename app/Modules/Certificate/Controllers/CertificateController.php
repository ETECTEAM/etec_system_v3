<?php

namespace App\Modules\Certificate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassCertificateRequest;
use App\Models\Course;
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
<<<<<<< HEAD
    private const TYPES = ['free', 'normal', 'scholarship', 'internship'];
=======
    private const TYPES = ['free', 'normal', 'scholarship', 'meal', 'internship'];
>>>>>>> 32208d4 (update_cirtificate)

    public function index(Request $request): Response
    {
        $type = $this->normaliseType($request->query('type', 'free'));

        return Inertia::render('backend/certificates/Index', [
            'type' => $type,
            'freeCertificates' => fn () => $this->freeCertificates($request),
            'freeCourses' => fn () => $this->customCourses('course_custom'),
            'normalCourses' => fn () => $this->customCourses('course_custom_normal'),
            'certificateRequests' => fn () => $this->classCertificateRequests($type),
            'generatedIds' => [
                'free' => $this->generateFreeId(),
                'normal' => $this->generateNormalId(),
            ],
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        $type = $this->normaliseType($request->query('type', 'normal'));

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
            ->when($type !== 'free', fn (Builder $query) => $this->whereRequestedCertificateClass($query, $type))
            ->when($type === 'normal', fn (Builder $query) => $this->whereRegularCertificateClass($query))
            ->when($type !== 'normal', fn (Builder $query) => $this->whereTypedCertificateClass($query, $type))
            ->latest('id')
            ->get();

        $printedCounts = DB::table('student_certificate_normal')
            ->select('study_class_id', DB::raw('COUNT(DISTINCT student_id) as printed_students'))
            ->where('certificate_type', $type)
            ->whereIn('study_class_id', $classes->pluck('id'))
            ->groupBy('study_class_id')
            ->pluck('printed_students', 'study_class_id');

        $classes = $classes
            ->map(fn (StudyClass $studyClass): array => [
                'id' => $studyClass->id,
                'category' => $studyClass->course?->track?->subCategory?->category?->name
                    ?? $studyClass->course?->track?->subCategory?->name
                    ?? $studyClass->course?->track?->name
                    ?? 'General',
                'course' => $studyClass->course?->title ?? $studyClass->title,
                'teacher_name' => $studyClass->teacher?->name ?? 'No teacher',
                'time' => $studyClass->time?->time_name ?? $this->classTime($studyClass),
                'class_type' => $studyClass->classType?->type_name ?? $studyClass->classTypeValue(),
                'total_students' => (int) $studyClass->total_students,
                'printed_students' => (int) ($printedCounts[$studyClass->id] ?? 0),
            ])
            ->values();

        return response()->json(['data' => $classes]);
    }

    public function students(Request $request, StudyClass $studyClass): JsonResponse
    {
        $type = $this->normaliseType($request->query('type', 'normal'));
        $certificateRequest = ClassCertificateRequest::query()
            ->where('study_class_id', $studyClass->id)
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
<<<<<<< HEAD
            'certificate_type' => ['required', Rule::in(['normal', 'scholarship', 'internship'])],
=======
            'certificate_type' => ['required', Rule::in(['normal', 'scholarship', 'meal', 'internship'])],
>>>>>>> 32208d4 (update_cirtificate)
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

    private function classCertificateRequests(string $type): array
    {
        return ClassCertificateRequest::query()
            ->with([
                'studyClass:id,title,teacher_id,course_id,term_id,time_id,class_type_id,status',
                'studyClass.course:id,title,course_track_id',
                'studyClass.course.track:id,name,sub_category_id',
                'studyClass.course.track.subCategory:id,name,category_id',
                'studyClass.course.track.subCategory.category:id,name',
                'studyClass.teacher:id,name',
                'requestedBy:id,name',
            ])
            ->whereHas('studyClass', fn (Builder $query) => $this->applyCertificateClassTypeFilter($query, $type))
            ->latest('requested_at')
            ->get()
            ->map(function (ClassCertificateRequest $request): array {
                $studyClass = $request->studyClass;

                return [
                    'id' => $request->id,
                    'study_class_id' => $request->study_class_id,
                    'class_title' => $studyClass?->title ?? 'Unknown class',
                    'course' => $studyClass?->course?->title ?? $studyClass?->title ?? '-',
                    'teacher_name' => $studyClass?->teacher?->name ?? '-',
                    'student_count' => (int) $request->student_count,
                    'student_ids' => collect($request->requested_student_ids ?? [])->map(fn ($id): int => (int) $id)->values()->all(),
                    'status' => $request->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $request->status)),
                    'certificate_type' => $request->certificate_type,
                    'requested_by' => $request->requestedBy?->name ?? '-',
                    'requested_at' => $request->requested_at?->format('Y-m-d h:i A'),
                    'note' => $request->note,
                ];
            })
            ->values()
            ->all();
    }

    private function applyCertificateClassTypeFilter(Builder $query, string $type): void
    {
        match ($type) {
            'free' => $query->whereHas('classType', fn (Builder $classType) => $classType->where('type_name', 'like', '%free%')),
            'scholarship' => $query->whereHas('classType', fn (Builder $classType) => $classType->where('type_name', 'like', '%scholar%')),
            'internship' => $query->whereHas('classType', fn (Builder $classType) => $classType->where('type_name', 'like', '%internship%')),
            default => $query->where(fn (Builder $regular) => $regular
                ->whereDoesntHave('classType')
                ->orWhereHas('classType', fn (Builder $classType) => $classType
                    ->where('type_name', 'not like', '%free%')
                    ->where('type_name', 'not like', '%scholar%')
                    ->where('type_name', 'not like', '%internship%'))),
        };
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

    private function whereRegularCertificateClass(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('classType', fn (Builder $classType) => $classType
                ->where('type_name', 'like', '%internship%')
                ->orWhere('type_name', 'like', '%intership%')
            )
            ->whereDoesntHave('course.track.category', fn (Builder $category) => $category
                ->where('name', 'like', '%internship%')
                ->orWhere('name', 'like', '%intership%')
            )
            ->whereDoesntHave('course.track', fn (Builder $track) => $track
                ->where('name', 'like', '%internship%')
                ->orWhere('name', 'like', '%intership%')
            );
    }

    private function whereTypedCertificateClass(Builder $query, string $type): Builder
    {
        if ($type === 'free') {
            return $query;
        }

        $keywords = match ($type) {
            'meal', 'internship' => ['internship', 'intership'],
            'scholarship' => ['scholar'],
            default => [$type],
        };

        return $query->where(function (Builder $query) use ($keywords): void {
            foreach ($keywords as $keyword) {
                $query
                    ->orWhereHas('classType', fn (Builder $classType) => $classType
                        ->where('type_name', 'like', "%{$keyword}%")
                    )
                    ->orWhereHas('course.track.category', fn (Builder $category) => $category
                        ->where('name', 'like', "%{$keyword}%")
                    )
                    ->orWhereHas('course.track', fn (Builder $track) => $track
                        ->where('name', 'like', "%{$keyword}%")
                    );
            }
        });
    }

    private function whereRequestedCertificateClass(Builder $query, string $type): Builder
    {
        return $query->whereHas('certificateRequests', fn (Builder $request) => $request
            ->where('certificate_type', $type)
            ->where('status', 'pending')
        );
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
