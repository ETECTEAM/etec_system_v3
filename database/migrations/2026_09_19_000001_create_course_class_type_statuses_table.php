<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Open/Closed per (course, class type) instead of one course-wide switch,
     * so closing a course for Scholarship no longer closes it for Physical and
     * Online. A missing row means Open.
     *
     * Kept apart from the schedule-scoped course_enroll_configs rows on purpose:
     * closing a class type must pause it without deleting its time slots, Max
     * Classes or start dates, so re-opening restores it exactly as it was.
     */
    public function up(): void
    {
        Schema::create('course_class_type_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('class_type_id')
                ->constrained('class_type', 'class_type_id')
                ->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->unique(['course_id', 'class_type_id']);
        });

        $this->carryOverClosedCourses();
    }

    public function down(): void
    {
        Schema::dropIfExists('course_class_type_statuses');
    }

    /**
     * A course that was Closed under the old course-wide switch stays hidden:
     * close every class type it could be offered under - its mapped class type
     * (or the default set when its track is unmapped) plus any class type it
     * already has time slots for. The old switch is no longer read, so without
     * this those courses would reappear on public registration.
     *
     * The course-wide row itself is left untouched - it still carries the
     * prices and start date - which also keeps rolling this migration back safe.
     */
    private function carryOverClosedCourses(): void
    {
        $closedCourseIds = DB::table('course_enroll_configs')
            ->whereNull('schedule_id')
            ->whereNull('time_id')
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'open'))
            ->pluck('course_id');

        if ($closedCourseIds->isEmpty()) {
            return;
        }

        $defaultTypeIds = DB::table('class_type')
            ->whereIn('type_name', ['Physical Class', 'Scholarship Class', 'Online Class'])
            ->pluck('class_type_id');

        $mappedTypeByCourse = DB::table('courses')
            ->leftJoin('course_tracks', 'course_tracks.id', '=', 'courses.course_track_id')
            ->whereIn('courses.id', $closedCourseIds)
            ->pluck('course_tracks.class_type_id', 'courses.id');

        $slotTypesByCourse = DB::table('course_enroll_configs')
            ->join('schedules', 'schedules.id', '=', 'course_enroll_configs.schedule_id')
            ->whereIn('course_enroll_configs.course_id', $closedCourseIds)
            ->select('course_enroll_configs.course_id', 'schedules.class_type_id')
            ->distinct()
            ->get()
            ->groupBy('course_id');

        $now = now();
        $rows = [];

        foreach ($closedCourseIds as $courseId) {
            $mappedTypeId = $mappedTypeByCourse->get($courseId);

            $classTypeIds = collect($mappedTypeId !== null ? [$mappedTypeId] : $defaultTypeIds)
                ->merge($slotTypesByCourse->get($courseId, collect())->pluck('class_type_id'))
                ->map(fn ($id) => (int) $id)
                ->unique();

            foreach ($classTypeIds as $classTypeId) {
                $rows[] = [
                    'course_id' => (int) $courseId,
                    'class_type_id' => $classTypeId,
                    'status' => 'closed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('course_class_type_statuses')->insert($chunk);
        }
    }
};
