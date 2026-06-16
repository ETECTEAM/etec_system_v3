<?php

namespace App\Modules\Registration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\ScheduleClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AdminRegistrationController extends Controller
{
    public function index()
    {
        $enrollments = Enrollment::with(['student', 'scheduleClass.time.term'])->latest()->get();
        
        $classes = ScheduleClass::with('time.term')->get()->map(function ($cls) {
            $cls->registered_count = $cls->registered_count; // triggers the accessor
            return $cls;
        });

        return Inertia::render('backend/registration/Index', [
            'enrollments' => $enrollments,
            'classes' => $classes,
        ]);
    }

    public function create()
    {
        $classes = ScheduleClass::with('time.term')->get()->map(function ($cls) {
            $cls->registered_count = $cls->registered_count;
            return $cls;
        });

        return Inertia::render('backend/registration/Create', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'nullable|date',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
        ]);

        DB::transaction(function () use ($validated) {
            $student = Student::create([
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
            ]);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $validated['class_id'],
                'enrollment_date' => now()->toDateString(),
            ]);
        });

        return redirect()->route('admin.registrations.index')->with('success', 'Student registered successfully.');
    }
}
