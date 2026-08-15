<?php

namespace App\Modules\Times\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('backend/times/Time', [
            'times' => Cache::remember(Time::CACHE_KEY, 3600, fn () => Time::orderBy('id', 'asc')->get()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/times/TimeCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'time_name' => ['required', 'string', 'max:255'],
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
        return Inertia::render('backend/times/TimeEdit', [
            'time' => $time,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Time $time)
    {
        $validated = $request->validate([
            'time_name' => ['required', 'string', 'max:255'],
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