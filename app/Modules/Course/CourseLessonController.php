<?php
// app/Modules/Course/CourseLessonController.php

namespace App\Modules\Course;

use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class CourseLessonController extends Controller
{
    /**
     * Display a listing of lessons.
     */
    public function index()
    {
        $lessons = CourseLesson::with('course')->orderBy('order_number')->get();
        $allCourses = Course::where('status', 'active')->get();

        return Inertia::render('backend/courses/Lessons', [
            'lessons' => $lessons,
            'allCourses' => $allCourses
        ]);
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        $courses = Course::where('status', 'active')->get();
        return Inertia::render('backend/courses/LessonForm', [
            'lesson' => null,
            'courses' => $courses
        ]);
    }

    /**
     * Store a newly created lesson in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url|max:255',
            'duration' => 'nullable|integer|min:0',
            'order_number' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive'
        ]);

        // Get the highest order number for this course
        $maxOrder = CourseLesson::where('course_id', $validated['course_id'])->max('order_number') ?? 0;

        CourseLesson::create([
            'course_id' => $validated['course_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'duration' => $validated['duration'] ?? 0,
            'order_number' => $validated['order_number'] ?? $maxOrder + 1,
            'status' => $validated['status'] ?? 'active'
        ]);

        return redirect()->route('course.lessons')->with('success', 'Lesson created successfully');
    }

    /**
     * Display the specified lesson.
     */
    public function show(CourseLesson $lesson)
    {
        $lesson->load('course');
        return Inertia::render('backend/courses/LessonShow', [
            'lesson' => $lesson
        ]);
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit(CourseLesson $lesson)
    {
        $courses = Course::where('status', 'active')->get();
        return Inertia::render('backend/courses/LessonForm', [
            'lesson' => $lesson,
            'courses' => $courses
        ]);
    }

    /**
     * Update the specified lesson in storage.
     */
    public function update(Request $request, CourseLesson $lesson)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url|max:255',
            'duration' => 'nullable|integer|min:0',
            'order_number' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive'
        ]);

        $lesson->update([
            'course_id' => $validated['course_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'duration' => $validated['duration'] ?? 0,
            'order_number' => $validated['order_number'] ?? $lesson->order_number,
            'status' => $validated['status'] ?? 'active'
        ]);

        return redirect()->route('course.lessons')->with('success', 'Lesson updated successfully');
    }

    /**
     * Remove the specified lesson from storage.
     */
    public function destroy(CourseLesson $lesson)
    {
        $lesson->delete();
        return redirect()->route('course.lessons')->with('success', 'Lesson deleted successfully');
    }

    /**
     * Reorder lessons.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'lessons' => 'required|array',
            'lessons.*.id' => 'required|exists:course_lessons,id',
            'lessons.*.order_number' => 'required|integer|min:1'
        ]);

        foreach ($validated['lessons'] as $lessonData) {
            CourseLesson::where('id', $lessonData['id'])->update(['order_number' => $lessonData['order_number']]);
        }

        return response()->json(['message' => 'Lessons reordered successfully']);
    }
}