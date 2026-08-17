<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A manual, staff-entered exception to an instructor's normal working time
 * (ShiftTemplate/InstructorAvailability) — e.g. "blocked Monday 09:00-10:30,
 * still teaching an old-system class". Never modifies the ShiftTemplate or
 * InstructorAvailability rows; it's purely an additional exclusion checked
 * alongside them during instructor selection (see instructorHasManualBlock()
 * in RegisterStudentForSchedule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructor_data')->cascadeOnDelete();
            // 1 = Monday .. 7 = Sunday, same convention as shift_template_blocks
            // and instructor_availabilities.day_of_week.
            $table->unsignedTinyInteger('day_of_week');
            $table->foreignId('time_id')->constrained('times')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['instructor_id', 'day_of_week', 'time_id', 'status'], 'instructor_schedule_blocks_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_schedule_blocks');
    }
};
