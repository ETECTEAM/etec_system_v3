<?php

namespace App\Modules\Registration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ScheduleClass;
use App\Models\Time;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminClassController extends Controller
{
    public function index()
    {
        $classes = ScheduleClass::with('time.term')->get()->map(function ($cls) {
            $cls->registered_count = $cls->registered_count;
            return $cls;
        });

        $times = Time::with('term')->get();

        return Inertia::render('backend/class/Index', [
            'classes' => $classes,
            'times' => $times,
        ]);
    }

    public function create()
    {
        $times = Time::with('term')->get();
        return Inertia::render('backend/class/Create', [
            'times' => $times,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:100',
            'time_id' => 'required|exists:times,id',
            'capacity' => 'required|integer|min:1',
        ]);

        ScheduleClass::create($validated);

        return redirect()->route('admin.classes.index')->with('success', 'Class created successfully.');
    }
}
