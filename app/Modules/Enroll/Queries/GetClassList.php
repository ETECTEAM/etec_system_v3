<?php

namespace App\Modules\Enroll\Queries;

use App\Models\CourseEnrollConfig;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GetClassList
{
    private ?array $termLabels = null;

    public function handle(Request $request): array
    {
        $search = trim($request->string('search')->toString());

        $classes = StudyClass::query()
            ->select([
                'id',
                'title',
                'course_id',
                'lesson_id',
                'teacher_id',
                'room_id',
                'class_type_id',
                'term_id',
                'time_id',
                'status',
                'capacity',
                'price',
                'document_price',
                'attendance_latitude',
                'attendance_longitude',
                'attendance_radius_meters',
                'enrollment_start_date',
                'start_date',
                'end_date',
            ])
            ->with([
                'course:id,title',
                'lesson:id,course_id,title',
                'teacher:id,name',
                'room:id,floor_id,room_number',
                'room.floor:id,building_id,name,level',
                'room.floor.building:id,name',
                'classType:class_type_id,type_name',
                'term:id,term_name',
                'time:id,time_name',
            ])
            ->withCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->where('status', '!=', 'cancelled')
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return [
            'classes' => $classes->through(fn (StudyClass $studyClass) => $this->presentClass($studyClass)),
            'depositSummary' => $this->summary(),
            'filters' => [
                'search' => $search,
            ],
        ];
    }

    // Full (unpaginated) class list for the "move student to another class"
    // picker — needs every open class, not just the current page. Returns the
    // individual fields (course/term/time/teacher/capacity) so the client can
    // render a pickable table and live-filter across all of them.
    public function forSelect(): array
    {
        return StudyClass::query()
            ->select(['id', 'title', 'course_id', 'teacher_id', 'term_id', 'time_id', 'capacity', 'start_date'])
            ->with([
                'course:id,title',
                'teacher:id,name',
                'term:id,term_name',
                'time:id,time_name',
            ])
            ->withCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->whereIn('status', ['upcoming', 'active', 'pre_end'])
            ->orderBy('title')
            ->get()
            ->map(function (StudyClass $studyClass): array {
                $currentStudents = (int) ($studyClass->current_students ?? 0);
                $capacity = (int) $studyClass->capacity;

                return [
                    'id' => $studyClass->id,
                    'title' => $studyClass->title,
                    'course' => $studyClass->course?->title ?? '-',
                    'term' => $studyClass->term?->term_name ?? '-',
                    'time' => $studyClass->time?->time_name ?? '-',
                    'teacher' => $studyClass->teacher?->name ?? '-',
                    'current_students' => $currentStudents,
                    'capacity' => $capacity,
                    'is_full' => $currentStudents >= $capacity,
                    'start_date' => $studyClass->start_date?->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->where('title', 'like', "%{$search}%")
                ->orWhereHas('course', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('lesson', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('teacher', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('room', fn (Builder $query) => $query->where('room_number', 'like', "%{$search}%"))
                ->orWhereHas('room.floor.building', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function presentClass(StudyClass $studyClass): array
    {
        $currentStudents = (int) ($studyClass->current_students ?? $studyClass->enrollments_count ?? 0);
        $capacity = (int) $studyClass->capacity;
        $availableSeats = max($capacity - $currentStudents, 0);
        $filledPercentage = $capacity > 0 ? round(($currentStudents / $capacity) * 100, 2) : 0;
        $studyDays = $studyClass->scheduleStudyDays();
        $classTypeValue = $studyClass->classTypeValue();
        $classTypeLabel = $studyClass->classType?->type_name
            ?? ($classTypeValue === 'online' ? 'Online Class' : 'Physical Class');

        $config = $this->resolveEnrollConfig($studyClass);
        $resolvedPrice = $config?->resolvedPrice() ?? (float) $studyClass->price;
        $resolvedDocumentPrice = $config !== null
            ? (float) $config->document_price
            : (float) $studyClass->document_price;

        return [
            'id' => $studyClass->id,
            'title' => $studyClass->title,
            'course' => $studyClass->course?->title,
            'course_price' => $studyClass->course?->price !== null ? (float) $studyClass->course->price : null,
            'course_document_price' => $studyClass->course?->document_price !== null ? (float) $studyClass->course->document_price : null,
            'lesson' => $studyClass->lesson?->title ?? '-',
            'teacher' => $studyClass->teacher?->name ?? '-',
            'building' => $studyClass->room?->floor?->building?->name ?? '-',
            'floor' => $studyClass->room?->floor?->name ?? '-',
            'room' => $studyClass->room?->room_number ?? ($studyClass->isOnline() ? 'Online' : '-'),
            'class_type' => $classTypeValue,
            'class_type_label' => $classTypeLabel,
            'status' => $classTypeLabel,
            'class_status' => $studyClass->status,
            'class_status_label' => str_replace('_', ' ', ucfirst($studyClass->status)),
            'study_days' => $studyDays,
            'term' => $this->termLabel($studyDays),
            'start_time' => $this->formatTime($studyClass->scheduleStartTime()),
            'end_time' => $this->formatTime($studyClass->scheduleEndTime()),
            'time' => $this->formatTime($studyClass->scheduleStartTime()).' - '.$this->formatTime($studyClass->scheduleEndTime()),
            'capacity' => $capacity,
            'price' => $resolvedPrice,
            'document_price' => $resolvedDocumentPrice,
            'attendance_latitude' => $studyClass->attendance_latitude !== null ? (float) $studyClass->attendance_latitude : null,
            'attendance_longitude' => $studyClass->attendance_longitude !== null ? (float) $studyClass->attendance_longitude : null,
            'attendance_radius_meters' => $studyClass->attendance_radius_meters !== null ? (int) $studyClass->attendance_radius_meters : null,
            'resolved_price' => $resolvedPrice,
            'resolved_document_price' => $resolvedDocumentPrice,
            'enrollment_start_date' => optional($studyClass->enrollment_start_date)->format('Y-m-d'),
            'start_date' => optional($studyClass->start_date)->format('Y-m-d'),
            'end_date' => optional($studyClass->end_date)->format('Y-m-d'),
            'students' => $currentStudents,
            'current_students' => $currentStudents,
            'available_seats' => $availableSeats,
            'filled_percentage' => $filledPercentage,
            'notifications' => 0,
        ];
    }

    private function resolveEnrollConfig(StudyClass $studyClass): ?CourseEnrollConfig
    {
        return CourseEnrollConfig::forCourseTime($studyClass->course_id, $studyClass->time_id);
    }

    private function summary(): array
    {
        $active = StudentEnrollment::query()->where('enrollment_status', 'active');

        return [
            'total_students' => (clone $active)->count(),
            'paid_students' => (clone $active)->where('payment_status', 'paid')->count(),
            'partial_students' => (clone $active)->where('payment_status', 'partial')->count(),
            'unpaid_students' => (clone $active)->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'total_deposit_collected' => (float) (clone $active)->sum('amount_paid'),
        ];
    }

    private function formatTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '-';
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

        return $this->termLabels = Term::query()
            ->select('term_name')
            ->orderBy('term_name')
            ->get()
            ->mapWithKeys(fn (Term $term) => [
                $this->studyDaysKey($this->parseTermDays($term->term_name)) => $term->term_name,
            ])
            ->filter(fn (string $label, string $key) => $key !== '')
            ->all();
    }

    private function parseTermDays(?string $termName): array
    {
        return StudyClass::parseTermDays($termName);
    }

    private function studyDaysKey(array $studyDays): string
    {
        return collect($studyDays)
            ->map(fn (string $day) => strtolower(trim($day)))
            ->sort()
            ->implode('|');
    }
}
