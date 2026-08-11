<?php
// app/Modules/Course/CourseController.php

namespace App\Modules\Course;

use App\Models\Course;
use App\Models\Category;
use App\Models\CourseEnrollConfig;
use App\Models\SubCategory;
use App\Models\CourseTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index()
    {
        // Get all courses with relationships
        $courses = Course::with(['track.subCategory.category'])->get();
        
        // Get all data for filters
        $allCategories = Category::where('status', 'active')->get();
        $allSubCategories = SubCategory::where('status', 'active')->get();
        $allTracks = CourseTrack::where('status', 'active')->get();

        return Inertia::render('backend/courses/Courses', [
            'courses' => $courses,
            'allCategories' => $allCategories,
            'allSubCategories' => $allSubCategories,
            'allTracks' => $allTracks
        ]);
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->get();
        $subCategories = SubCategory::where('status', 'active')->get();
        $tracks = CourseTrack::where('status', 'active')->get();

        return Inertia::render('backend/courses/CourseForm', [
            'course' => null,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'tracks' => $tracks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_track_id' => 'required|exists:course_tracks,id',
            'title' => 'required|string|max:255|unique:courses,title',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // Handle file upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $course = Course::create([
            'course_track_id' => $validated['course_track_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'level' => $validated['level'] ?? 'beginner',
            'status' => $validated['status'] ?? 'active',
            'thumbnail' => $thumbnailPath
        ]);

        // Price lives on CourseEnrollConfig, not the courses table (see the
        // migration that dropped price/document_price from courses).
        CourseEnrollConfig::query()->updateOrCreate(
            ['course_id' => $course->id],
            ['price' => $validated['price'] ?? 0]
        );

        return redirect()->route('course.courses')->with('success', 'Course created successfully');
    }

    public function show(Course $course)
    {
        $course->load(['track.subCategory.category', 'lessons']);
        return Inertia::render('backend/courses/CourseShow', [
            'course' => $course
        ]);
    }

    public function edit(Course $course)
    {
        $course->load('track.subCategory.category', 'enrollConfig');

        $categories = Category::where('status', 'active')->get();
        $subCategories = SubCategory::where('status', 'active')->get();
        $tracks = CourseTrack::where('status', 'active')->get();

        return Inertia::render('backend/courses/CourseForm', [
            'course' => $course,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'tracks' => $tracks
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_track_id' => 'required|exists:course_tracks,id',
            'title' => 'required|string|max:255|unique:courses,title,' . $course->id,
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            // Store new thumbnail
            $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            $validated['thumbnail'] = $thumbnailPath;
        }

        // Remove thumbnail from validated if not uploaded (to keep existing)
        if (!isset($validated['thumbnail'])) {
            unset($validated['thumbnail']);
        }

        $course->update([
            'course_track_id' => $validated['course_track_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'level' => $validated['level'] ?? 'beginner',
            'status' => $validated['status'] ?? 'active',
            'thumbnail' => $validated['thumbnail'] ?? $course->thumbnail
        ]);

        CourseEnrollConfig::query()->updateOrCreate(
            ['course_id' => $course->id],
            ['price' => $validated['price'] ?? 0]
        );

        return redirect()->route('course.courses')->with('success', 'Course updated successfully');
    }

    public function destroy(Course $course)
    {
        // Delete thumbnail
        if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();
        return redirect()->route('course.courses')->with('success', 'Course deleted successfully');
    }
}
