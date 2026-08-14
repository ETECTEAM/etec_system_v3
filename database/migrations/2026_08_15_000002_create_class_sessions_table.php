<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per class per calendar date it meets — the piece study_classes never stored
 * (it only has a term's weekday pattern + a time slot, not concrete dates). Generated
 * daily by GenerateClassSessions; the auto-record scheduler acts on rows still 'pending'.
 *
 * instructor_id is who is responsible for THIS date, not always the class owner: a class
 * shared between two instructors (study_class_instructors — "Collapse Class") has each
 * teaching different days, so a Wednesday session belongs to whichever instructor's own
 * term covers Wednesday.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_class_id')
                ->constrained('study_classes')
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('session_date');
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');

            // pending -> recorded (instructor submitted) | auto_recorded (system did) |
            // skipped (class had zero active students) | missed (end time passed while
            // still pending — never auto-recorded after the fact, left for an admin).
            $table->string('status', 20)->default('pending');

            $table->dateTime('recorded_at')->nullable();

            // Snapshot of the grace period in effect when this row was recorded, so a
            // later config change never retroactively rewrites what already happened.
            $table->unsignedInteger('grace_minutes_used')->nullable();

            $table->timestamps();

            $table->unique(['study_class_id', 'session_date']);
            $table->index(['status', 'scheduled_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
