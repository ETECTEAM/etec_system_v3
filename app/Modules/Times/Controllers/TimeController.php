<?php

namespace App\Modules\Times\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Time::with('term');

        // SEARCH
        if ($request->filled('search')) {
            $query->where('time_name', 'like', '%' . $request->search . '%');
        }

        // ✅ TERM FILTER (ADD THIS)
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        return Inertia::render('backend/times/Index', [
            'times' => $query
                ->latest()
                ->paginate(7)
                ->withQueryString(),

            'filters' => [
                'search' => $request->search ?? '',
                'term_id' => $request->term_id ?? '', // ✅ ADD THIS
            ],

            'terms' => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/times/Create', [
            'terms' => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'time_name' => ['required', 'string', 'max:255'],
            'term_id'   => ['required', 'exists:terms,id'],
        ]);

        Time::create($validated);

        return redirect()
            ->route('times.index')
            ->with('success', 'Time created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Time $time)
    {
        return Inertia::render('backend/times/Edit', [
            'time' => $time,
            'terms' => Term::select('id', 'term_name')
                ->orderBy('term_name')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Time $time)
    {
        $validated = $request->validate([
            'time_name' => ['required', 'string', 'max:255'],
            'term_id'   => ['required', 'exists:terms,id'],
        ]);

        $time->update($validated);

        return redirect()
            ->route('times.index')
            ->with('success', 'Time updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Time $time)
    {
        $time->delete();

        return redirect()
            ->route('times.index')
            ->with('success', 'Time deleted successfully.');
    }
}