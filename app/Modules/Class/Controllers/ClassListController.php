<?php

namespace App\Modules\Class\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassList;
use App\Models\ClassType;
use App\Models\Floor;
use App\Models\Room;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClassListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $classLists = ClassList::with([
            'teacher', 'term', 'time', 'floor', 'room', 'classType',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $classLists->where(function ($query) use ($search) {
                $query->where('status', 'like', "%{$search}%")
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

        return Inertia::render('backend/classes/class-list/index', [
            'classLists' => $classLists,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'class_type' => $request->class_type ?? 'all',
                'term' => $request->term ?? 'all',
                'time' => $request->time ?? 'all',
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
            'teacher_id'    => ['nullable', 'exists:users,id'],
            'course_id'     => ['nullable', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'exists:lessons,id'],
            'term_id'       => ['nullable', 'exists:terms,id'],
            'time_id'       => ['nullable', 'exists:times,id'],
            'building_id'   => ['nullable', 'exists:buildings,id'],
            'floor_id'      => ['nullable', 'exists:floors,id'],
            'room_id'       => ['nullable', 'exists:rooms,id'],
            'class_type_id' => ['nullable', 'exists:class_type,class_type_id'],
            'student_count' => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(['progress', 'completed', 'cancelled'])],
        ]);

        ClassList::create($validated);

        return redirect()->route('class-list.index')->with('success', 'Class created successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/classes/class-list/create', [
            'teachers' => [],
            'courses' => [],
            'lessons' => [],
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'buildings' => [],
            'floors' => Floor::select('id', 'name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ClassList $classList)
    {
        $classList->load([
            'teacher', 'term', 'time', 'floor', 'room', 'classType',
        ]);

        return Inertia::render('backend/classes/class-list/show', [
            'classList' => $classList,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassList $classList)
    {
        return Inertia::render('backend/classes/class-list/edit', [
            'classList' => $classList->load(['term', 'time', 'floor', 'room', 'classType']),
            'teachers' => [],
            'courses' => [],
            'lessons' => [],
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'buildings' => [],
            'floors' => Floor::select('id', 'name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassList $classList)
    {
        $validated = $request->validate([
            'teacher_id'    => ['sometimes', 'nullable', 'exists:users,id'],
            'course_id'     => ['sometimes', 'nullable', 'exists:courses,id'],
            'lesson_id'     => ['sometimes', 'nullable', 'exists:lessons,id'],
            'term_id'       => ['sometimes', 'nullable', 'exists:terms,id'],
            'time_id'       => ['sometimes', 'nullable', 'exists:times,id'],
            'building_id'   => ['sometimes', 'nullable', 'exists:buildings,id'],
            'floor_id'      => ['sometimes', 'nullable', 'exists:floors,id'],
            'room_id'       => ['sometimes', 'nullable', 'exists:rooms,id'],
            'class_type_id' => ['sometimes', 'nullable', 'exists:class_type,class_type_id'],
            'student_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status'        => ['sometimes', 'nullable', 'string', Rule::in(['progress', 'completed', 'cancelled'])],
        ]);

        $classList->update($validated);

        return redirect()->route('class-list.index')->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassList $classList)
    {
        $classList->delete();

        return redirect()->route('class-list.index')->with('success', 'Class deleted successfully.');
    }
}