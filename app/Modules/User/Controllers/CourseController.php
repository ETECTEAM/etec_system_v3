<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    // Show all courses
    public function index()
    {
        $courses = Course::paginate(10);

        return Inertia::render('backend/courses/Index', [
            'courses' => $courses
        ]);
    }

    // Show create form
    public function create()
    {
        return Inertia::render('backend/courses/Create');
    }

    // Store new course
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        Course::create([
            'title' => $request->title,
            'description' => $request->description
        ]);

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully!');
    }

    // Show edit form
    public function edit($id)
    {
        $course = Course::findOrFail($id);

        return Inertia::render('backend/courses/Edit', [
            'course' => $course
        ]);
    }

    // Update course
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        $course->update([
            'title' => $request->title,
            'description' => $request->description
        ]);

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully!');
    }

    // Delete course
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}