<?php

namespace App\Modules\Certificate\Controllers;

use App\Http\Controllers\Controller;
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
    private const TYPES = ['free', 'normal', 'scholarship', 'meal'];

    public function index(Request $request): Response
    {
        $type = $this->normaliseType($request->query('type', 'free'));

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

        $classes = StudyClass::query()
            ->with([
                'course:id,title,course_track_id',
                'course.track:id,name,category_id',
                'course.track.category:id,name',
                'teacher:id,name',
                'time:id,time_name',
                'classType:class_type_id,type_name',
            ])
            ->withCount([
                'enrollments as total_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->whereIn('status', ['pre_end', 'ended', 'completed', 'active'])
            ->when($type !== 'normal', fn (Builder $query) => $query->whereHas(
                'classType',
                fn (Builder $classType) => $classType->where('type_name', 'like', '%scholar%')->orWhere('type_name', 'like', '%meal%')
            ))
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
                'category' => $studyClass->course?->track?->category?->name
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

        $students = StudentEnrollment::query()
            ->with([
                'student:id,full_name,gender,phone',
                'student.certificates' => fn ($query) => $query
                    ->where('study_class_id', $studyClass->id)
                    ->where('certificate_type', $type),
            ])
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active')
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
            'certificate_type' => ['required', Rule::in(['normal', 'scholarship', 'meal'])],
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
