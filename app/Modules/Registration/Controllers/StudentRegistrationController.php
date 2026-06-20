<?php

namespace App\Modules\Registration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\ScheduleClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StudentRegistrationController extends Controller
{
    public function create()
    {
        // Only show classes that are not completely full, or show all with capacity
        $classes = ScheduleClass::with('time.term')->get()->map(function ($cls) {
            $cls->registered_count = $cls->registered_count;
            return $cls;
        });

        return Inertia::render('frontend/Register', [
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

        // Optionally redirect to a success page, but for now redirect back with success
        return redirect()->back()->with('success', 'You have successfully registered for the class.');
    }
}
