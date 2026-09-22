<?php

namespace App\Modules\Class\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Room;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use App\Modules\Enroll\Services\InstructorCourseEligibility;
use App\Modules\Room\Services\RoomAvailability;
use App\Support\InstructorDisplayName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ClassListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Backed by study_classes (StudyClass), the single source of truth for
     * classes across the app. class_list was a duplicate table and has been
     * dropped.
     */
    public function index(Request $request)
    {
        $classLists = StudyClass::with([
            'teacher', 'course', 'lesson', 'term', 'time', 'room.floor.building', 'classType',
        ])->withCount([
            'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $classLists->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('classType', fn ($q) => $q->where('type_name', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('term', fn ($q) => $q->where('term_name', 'like', "%{$search}%"))
                    ->orWhereHas('time', fn ($q) => $q->where('time_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $classLists->where('status', $request->status);
        }

        if ($request->filled('class_type') && $request->class_type !== 'all') {
            $classLists->whereHas('classType', fn ($query) => $query->where('type_name', $request->class_type));
        }

        if ($request->filled('term') && $request->term !== 'all') {
            $classLists->whereHas('term', fn ($query) => $query->where('term_name', $request->term));
        }

        if ($request->filled('time') && $request->time !== 'all') {
            $classLists->whereHas('time', fn ($query) => $query->where('time_name', $request->time));
        }

        $classLists = $classLists->latest()->paginate(20)->withQueryString();
        $classLists->getCollection()->each(fn (StudyClass $class) => $this->stripTeacherName($class));

        return Inertia::render('backend/classes/class-list/ClassList', [
            'classLists' => $classLists,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'class_type' => $request->class_type ?? '',
                'term' => $request->term ?? '',
                'time' => $request->time ?? '',
            ],
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => ['nullable', 'string', 'max:255'],
            'teacher_id'    => ['nullable', 'exists:users,id'],
            'course_id'     => ['required', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'integer', Rule::exists('course_lessons', 'id')->where('course_id', $request->input('course_id'))],
            'term_id'       => ['required', 'exists:terms,id'],
            'time_id'       => ['required', 'exists:times,id'],
            'room_id'       => ['required', 'exists:rooms,id'],
            'class_type_id' => ['required', 'exists:class_type,class_type_id'],
            'capacity'      => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
        ]);

        $validated['title'] = filled($validated['title'] ?? null)
            ? $validated['title']
            : Course::findOrFail($validated['course_id'])->title;

        $this->assertRoomFree($validated);
        $this->assertInstructorCanTeachCourse($validated);
        $this->assertInstructorFree($validated);

        StudyClass::create($validated);

        return redirect()->route('class-list.index')->with('success', 'Class created successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(GetClassFormOptions $formOptions)
    {
        return Inertia::render('backend/classes/class-list/ClassListCreate', [
            'teachers' => $this->teacherOptions(),
            'courses' => Course::select('id', 'title')->get(),
            'lessons' => CourseLesson::select('id', 'course_id', 'title')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
            'scheduleGroups' => $formOptions->scheduleGroups(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StudyClass $classList)
    {
        $classList->load(['teacher', 'course', 'lesson', 'term', 'time', 'room.floor.building', 'classType']);
        $classList->loadCount([
            'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
        ]);
        $this->stripTeacherName($classList);

        return Inertia::render('backend/classes/class-list/ClassListShow', [
            'classList' => $classList,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudyClass $classList, GetClassFormOptions $formOptions)
    {
        return Inertia::render('backend/classes/class-list/ClassListEdit', [
            'classList' => $classList->load(['course', 'lesson', 'term', 'time', 'room', 'classType']),
            'teachers' => $this->teacherOptions(),
            'courses' => Course::select('id', 'title')->get(),
            'lessons' => CourseLesson::select('id', 'course_id', 'title')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
            'scheduleGroups' => $formOptions->scheduleGroups(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudyClass $classList)
    {
        $validated = $request->validate([
            'title'         => ['nullable', 'string', 'max:255'],
            'teacher_id'    => ['nullable', 'exists:users,id'],
            'course_id'     => ['required', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'integer', Rule::exists('course_lessons', 'id')->where('course_id', $request->input('course_id'))],
            'term_id'       => ['required', 'exists:terms,id'],
            'time_id'       => ['required', 'exists:times,id'],
            'room_id'       => ['required', 'exists:rooms,id'],
            'class_type_id' => ['required', 'exists:class_type,class_type_id'],
            'capacity'      => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
        ]);

        $validated['title'] = filled($validated['title'] ?? null)
            ? $validated['title']
            : Course::findOrFail($validated['course_id'])->title;

        $this->assertRoomFree($validated, $classList);
        $this->assertInstructorCanTeachCourse($validated);
        $this->assertInstructorFree($validated, $classList);

        $classList->update($validated);

        return redirect()->route('class-list.index')->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyClass $classList)
    {
        $classList->delete();

        return redirect()->route('class-list.index')->with('success', 'Class deleted successfully.');
    }

    // A room holds one class at a time. Editing without touching room or slot is left alone.
    private function assertRoomFree(array $validated, ?StudyClass $existing = null): void
    {
        $roomId = $validated['room_id'] ?? $existing?->room_id;
        $termId = $validated['term_id'] ?? $existing?->term_id;
        $timeId = $validated['time_id'] ?? $existing?->time_id;

        if (! $roomId || ! $termId || ! $timeId) {
            return;
        }

        if ($existing
            && (int) $existing->room_id === (int) $roomId
            && (int) $existing->term_id === (int) $termId
            && (int) $existing->time_id === (int) $timeId) {
            return;
        }

        $reason = app(RoomAvailability::class)->unavailableReason((int) $roomId, (int) $termId, (int) $timeId, $existing?->id);

        if ($reason !== null) {
            throw ValidationException::withMessages(['room_id' => $reason]);
        }
    }

    private function assertInstructorFree(array $validated, ?StudyClass $existing = null): void
    {
        if (empty($validated['teacher_id'])) {
            return;
        }

        $reason = app(InstructorAssignmentAvailability::class)->unavailableReason(
            (int) $validated['teacher_id'],
            (int) $validated['term_id'],
            (int) $validated['time_id'],
            $existing?->id,
        );

        if ($reason !== null) {
            throw ValidationException::withMessages(['teacher_id' => $reason]);
        }
    }

    private function assertInstructorCanTeachCourse(array $validated): void
    {
        if (empty($validated['teacher_id'])) {
            return;
        }

        $teacher = User::query()->with('instructorData:id,user_id,specialization')->findOrFail($validated['teacher_id']);
        $course = Course::query()->with('track.subCategory')->findOrFail($validated['course_id']);

        if (! app(InstructorCourseEligibility::class)->canTeach($teacher, $course)) {
            throw ValidationException::withMessages(['teacher_id' => 'This instructor does not have the required course specialization.']);
        }
    }

    private function teacherOptions()
    {
        return User::role('instructor')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $teacher) => [
                'id' => $teacher->id,
                'name' => InstructorDisplayName::format($teacher->name, 'Unknown'),
            ]);
    }

    private function stripTeacherName(StudyClass $class): void
    {
        if ($class->relationLoaded('teacher') && $class->teacher) {
            $class->teacher->name = InstructorDisplayName::format($class->teacher->name, 'Unknown');
        }
    }
}
