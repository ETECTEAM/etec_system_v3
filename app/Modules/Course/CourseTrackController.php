<?php
// app/Modules/Course/CourseTrackController.php

namespace App\Modules\Course;

use App\Models\CourseTrack;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class CourseTrackController extends Controller
{
    /**
     * Display a listing of tracks.
     */
    public function index()
    {
        $tracks = CourseTrack::with('subCategory')->get();
        return Inertia::render('backend/courses/Tracks', [
            'tracks' => $tracks
        ]);
    }

    /**
     * Show the form for creating a new track.
     */
    public function create()
    {
        $subCategories = SubCategory::where('status', 'active')->get();
        return Inertia::render('backend/courses/TrackForm', [
            'track' => null,
            'subCategories' => $subCategories
        ]);
    }

    /**
     * Store a newly created track in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'name' => 'required|string|max:255|unique:course_tracks,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive'
        ]);

        CourseTrack::create([
            'sub_category_id' => $validated['sub_category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active'
        ]);

        return redirect()->route('course.tracks')->with('success', 'Track created successfully');
    }

    /**
     * Display the specified track.
     */
    public function show(CourseTrack $track)
    {
        $track->load('subCategory', 'courses');
        return Inertia::render('backend/courses/TrackShow', [
            'track' => $track
        ]);
    }

    /**
     * Show the form for editing the specified track.
     */
    public function edit(CourseTrack $track)
    {
        $subCategories = SubCategory::where('status', 'active')->get();
        return Inertia::render('backend/courses/TrackForm', [
            'track' => $track,
            'subCategories' => $subCategories
        ]);
    }

    /**
     * Update the specified track in storage.
     */
    public function update(Request $request, CourseTrack $track)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'name' => 'required|string|max:255|unique:course_tracks,name,' . $track->id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive'
        ]);

        $track->update([
            'sub_category_id' => $validated['sub_category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active'
        ]);

        return redirect()->route('course.tracks')->with('success', 'Track updated successfully');
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(CourseTrack $track)
    {
        // Check if track has courses
        if ($track->courses()->count() > 0) {
            return back()->with('error', 'Cannot delete track with existing courses.');
        }

        $track->delete();
        return redirect()->route('course.tracks')->with('success', 'Track deleted successfully');
    }
}