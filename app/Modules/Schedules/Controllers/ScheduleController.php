<?php

namespace App\Modules\Schedules\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\Time;
use App\Models\ClassType;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['classType', 'term', 'times']);

        // SEARCH (class type OR term)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('classType', function ($sub) use ($request) {
                    $sub->where('type_name', 'like', '%' . $request->search . '%');
                })->orWhereHas('term', function ($sub) use ($request) {
                    $sub->where('term_name', 'like', '%' . $request->search . '%');
                });
            });
        }

        // FILTER: CLASS TYPE
        if ($request->filled('class_type_id')) {
            $query->where('class_type_id', $request->class_type_id);
        }

        // FILTER: TERM
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        // FILTER: TIME (many-to-many)
        if ($request->filled('time_id')) {
            $query->whereHas('times', function ($q) use ($request) {
                $q->where('times.id', $request->time_id);
            });
        }

        // Grouped by class type: each group holds its schedules (one per term),
        // each schedule holds its time slots.
        $scheduleGroups = $query->get()
            ->sortBy(fn ($schedule) => $schedule->classType?->type_name ?? '')
            ->groupBy('class_type_id')
            ->map(function ($schedules) {
                $first = $schedules->first();

                return [
                    'class_type_id' => $first->class_type_id,
                    'class_type_name' => $first->classType?->type_name ?? '-',
                    'schedules' => $schedules->sortBy(fn ($schedule) => $schedule->term?->term_name ?? '')
                        ->values()
                        ->map(fn ($schedule) => [
                            'id' => $schedule->id,
                            'term_id' => $schedule->term_id,
                            'term_name' => $schedule->term?->term_name ?? '-',
                            'times' => $schedule->times->map(fn ($time) => [
                                'id' => $time->id,
                                'time_name' => $time->time_name,
                            ])->values(),
                        ]),
                ];
            })
            ->values();

        return Inertia::render('backend/schdule/Schedule', [
            'scheduleGroups' => $scheduleGroups,

            // IMPORTANT: return ALL filters
            'filters' => [
                'search' => $request->search ?? '',
                'class_type_id' => $request->class_type_id ?? '',
                'term_id' => $request->term_id ?? '',
                'time_id' => $request->time_id ?? '',
            ],

            'classTypes' => ClassType::select('class_type_id', 'type_name')
                ->orderBy('type_name')
                ->get(),

            'terms' => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),

            'times' => Time::select('id', 'time_name')
                ->orderBy('time_name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/schdule/ScheduleCreate', [
            'classTypes' => ClassType::select('class_type_id', 'type_name')
                ->orderBy('type_name')
                ->get(),
            'terms' => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),
            'times' => Time::select('id', 'time_name')
                ->orderBy('time_name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_type_id' => ['required', 'exists:class_type,class_type_id'],
            'term_id'       => ['required', 'exists:terms,id'],
            'time_ids'      => ['required', 'array'],
            'time_ids.*'    => ['exists:times,id'],
        ]);

        $schedule = Schedule::create([
            'class_type_id' => $validated['class_type_id'],
            'term_id'       => $validated['term_id'],
        ]);

        $schedule->times()->sync($validated['time_ids']);

        return redirect()
            ->route('schdule.index')
            ->with('success', 'Schedule created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schdule)
    {
        return Inertia::render('backend/schdule/ScheduleEdit', [
            'schdule' => $schdule->load('times'),
            'classTypes' => ClassType::select('class_type_id', 'type_name')
                ->orderBy('type_name')
                ->get(),
            'terms'   => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),
            'times'   => Time::select('id', 'time_name')
                ->orderBy('time_name')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schdule)
    {
        $validated = $request->validate([
            'class_type_id' => ['required', 'exists:class_type,class_type_id'],
            'term_id'       => ['required', 'exists:terms,id'],
            'time_ids'      => ['required', 'array'],
            'time_ids.*'    => ['exists:times,id'],
        ]);

        $schdule->update([
            'class_type_id' => $validated['class_type_id'],
            'term_id'       => $validated['term_id'],
        ]);

        $schdule->times()->sync($validated['time_ids']);

        return redirect()
            ->route('schdule.index')
            ->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schdule)
    {
        $schdule->delete();

        return redirect()
            ->route('schdule.index')
            ->with('success', 'Schedule deleted successfully.');
    }

    /**
     * Remove every schedule (all terms) belonging to a class type.
     */
    public function destroyByClassType(int $classTypeId)
    {
        Schedule::where('class_type_id', $classTypeId)->delete();

        return redirect()
            ->route('schdule.index')
            ->with('success', 'Schedules deleted successfully.');
    }
}
