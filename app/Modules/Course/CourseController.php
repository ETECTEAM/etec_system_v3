<?php

// app/Modules/Course/CourseController.php

namespace App\Modules\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\CourseTrack;
use App\Models\Schedule;
use App\Models\SubCategory;
use App\Modules\Enroll\Queries\GetCourseClassSchedules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage; // FILE: disabled - not using file uploads
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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

        return Inertia::render('backend/courses/Course/CourseIndex', [
            'courses' => $courses,
            'allCategories' => $allCategories,
            'allSubCategories' => $allSubCategories,
            'allTracks' => $allTracks,
        ]);
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->get();
        $subCategories = SubCategory::where('status', 'active')->get();
        $tracks = CourseTrack::where('status', 'active')->get();

        return Inertia::render('backend/courses/Course/CourseForm', [
            'course' => null,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'tracks' => $tracks,
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
            // FILE: disabled - not using file uploads
            // 'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // FILE: disabled - not using file uploads
        // $thumbnailPath = null;
        // if ($request->hasFile('thumbnail')) {
        //     $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        // }

        $course = Course::create([
            'course_track_id' => $validated['course_track_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'level' => $validated['level'] ?? 'beginner',
            'status' => $validated['status'] ?? 'active',
            // 'thumbnail' => $thumbnailPath, // FILE: disabled
        ]);

        // Price lives on CourseEnrollConfig, not the courses table (see the
        // migration that dropped price/document_price from courses). The form's
        // single price input maps to the default schedule's course price.
        if (array_key_exists('price', $validated)) {
            CourseEnrollConfig::query()->updateOrCreate(
                ['course_id' => $course->id, 'schedule_id' => null, 'time_id' => null],
                ['course_price' => $validated['price'] ?? 0]
            );
        }

        return redirect()->route('course.courses')->with('success', 'Course created successfully');
    }

    public function show(Course $course)
    {
        $course->load(['track.subCategory.category', 'lessons']);

        return Inertia::render('backend/courses/CourseShow', [
            'course' => $course,
        ]);
    }

    public function edit(Course $course, GetCourseClassSchedules $classSchedules)
    {
        $course->load('track.subCategory.category', 'enrollConfig');

        $categories = Category::where('status', 'active')->get();
        $subCategories = SubCategory::where('status', 'active')->get();
        $tracks = CourseTrack::where('status', 'active')->get();

        return Inertia::render('backend/courses/Course/CourseForm', [
            'course' => $course,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'tracks' => $tracks,
            'classSchedules' => $classSchedules->handle($course),
        ]);
    }

    // Toggle: creates an open row if none exists, deletes it if one does.
    public function toggleSchedule(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            // Must actually belong to schedule_id via schedule_time.
            'time_id' => [
                'required',
                'integer',
                Rule::exists('schedule_time', 'time_id')->where('schedule_id', $request->input('schedule_id')),
            ],
        ]);

        $schedule = Schedule::query()->with('classType')->findOrFail($validated['schedule_id']);

        abort_unless(
            in_array($schedule->classType?->type_name, GetCourseClassSchedules::AVAILABLE_CLASS_TYPES, true),
            422,
            'This class type is not available for course scheduling.'
        );

        $existing = CourseEnrollConfig::query()
            ->where('course_id', $course->id)
            ->where('schedule_id', $schedule->id)
            ->where('time_id', $validated['time_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['is_open' => false]);
        }

        CourseEnrollConfig::query()->create([
            'course_id' => $course->id,
            'schedule_id' => $schedule->id,
            'time_id' => $validated['time_id'],
            'status' => 'open',
            'unit_price' => 0,
            'course_price' => 0,
            'selected_price_type' => CourseEnrollConfig::PRICE_TYPE_COURSE,
            'document_price' => 5,
        ]);

        return response()->json(['is_open' => true]);
    }

    // Set (or clear) how many live classes this course may run in one class-type
    // + term + time slot. Writes max_classes on the same schedule-scoped row
    // toggleSchedule() creates, so the slot must already be open. 0 or null =
    // no limit (stored as null).
    public function setScheduleMaxClasses(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'time_id' => ['required', 'integer'],
            'max_classes' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $config = CourseEnrollConfig::query()
            ->where('course_id', $course->id)
            ->where('schedule_id', $validated['schedule_id'])
            ->where('time_id', $validated['time_id'])
            ->first();

        abort_unless($config !== null, 422, 'Open this time slot before setting a class limit.');

        // 0 (and null) both mean unlimited.
        $config->update(['max_classes' => ($validated['max_classes'] ?? 0) ?: null]);

        return response()->json(['max_classes' => $config->max_classes]);
    }

    // Bulk counterpart to toggleSchedule() - opens or closes every time slot
    // under one class type for this course in a single request, instead of
    // clicking each badge individually.
    public function setClassTypeAvailability(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'class_type_id' => ['required', 'integer', 'exists:class_type,class_type_id'],
            'open' => ['required', 'boolean'],
        ]);

        $classType = ClassType::query()->findOrFail($validated['class_type_id']);

        abort_unless(
            in_array($classType->type_name, GetCourseClassSchedules::AVAILABLE_CLASS_TYPES, true),
            422,
            'This class type is not available for course scheduling.'
        );

        $schedules = Schedule::query()
            ->where('class_type_id', $classType->class_type_id)
            ->with('times:id')
            ->get();

        DB::transaction(function () use ($course, $schedules, $validated) {
            if ($validated['open']) {
                foreach ($schedules as $schedule) {
                    foreach ($schedule->times as $time) {
                        CourseEnrollConfig::query()->firstOrCreate(
                            ['course_id' => $course->id, 'schedule_id' => $schedule->id, 'time_id' => $time->id],
                            [
                                'status' => 'open',
                                'unit_price' => 0,
                                'course_price' => 0,
                                'selected_price_type' => CourseEnrollConfig::PRICE_TYPE_COURSE,
                                'document_price' => 5,
                            ]
                        );
                    }
                }
            } else {
                CourseEnrollConfig::query()
                    ->where('course_id', $course->id)
                    ->whereIn('schedule_id', $schedules->pluck('id'))
                    ->delete();
            }
        });

        return response()->json(['open' => $validated['open']]);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_track_id' => 'required|exists:course_tracks,id',
            'title' => 'required|string|max:255|unique:courses,title,'.$course->id,
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            // FILE: disabled - not using file uploads
            // 'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // FILE: disabled - not using file uploads
        // if ($request->hasFile('thumbnail')) {
        //     // Delete old thumbnail
        //     if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
        //         Storage::disk('public')->delete($course->thumbnail);
        //     }
        //     // Store new thumbnail
        //     $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        //     $validated['thumbnail'] = $thumbnailPath;
        // }

        // Remove thumbnail from validated if not uploaded (to keep existing)
        if (! isset($validated['thumbnail'])) {
            unset($validated['thumbnail']);
        }

        $course->update([
            'course_track_id' => $validated['course_track_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'level' => $validated['level'] ?? 'beginner',
            'status' => $validated['status'] ?? 'active',
            'thumbnail' => $validated['thumbnail'] ?? $course->thumbnail,
        ]);

        CourseEnrollConfig::query()->updateOrCreate(
            ['course_id' => $course->id, 'schedule_id' => null, 'time_id' => null],
            ['course_price' => $validated['price'] ?? 0]
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $course->fresh('track.subCategory.category')]);
        }

        return redirect()->route('course.courses')->with('success', 'Course updated successfully');
    }

    public function destroy(Course $course)
    {
        // FILE: disabled - not using file uploads
        // if ($course->thumbnail && Storage::disk('public')->exists($course->thumbnail)) {
        //     Storage::disk('public')->delete($course->thumbnail);
        // }

        $course->delete();

        return redirect()->route('course.courses')->with('success', 'Course deleted successfully');
    }
}
