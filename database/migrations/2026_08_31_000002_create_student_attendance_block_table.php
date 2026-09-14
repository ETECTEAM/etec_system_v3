<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The absence-block ledger. One row is raised per (student tel + course) cycle
 * phase: a pending 'absence' block (soft lock) that an admin approves, then a
 * pending 'hard_lock' block that only a super_admin can unlock.
 *
 * "Open block" (the only kind that locks attendance or blocks a duplicate) =
 *   is_approved = 0 AND rejected_at IS NULL
 *
 * The cycle is keyed by student_tel + course_id (one person across every class
 * in the same course), NOT study_class_id. student_tel is a snapshot of
 * students.phone taken when the block was raised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_block', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('student_tel', 20);
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('study_class_id')->nullable()->constrained('study_classes')->nullOnDelete();

            $table->enum('block_type', ['absence', 'hard_lock']);
            $table->boolean('is_approved')->default(false);

            $table->timestamp('blocked_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('admin_comment')->nullable();

            $table->date('cycle_start_date');

            $table->timestamps();

            // Explicit short names - the auto-generated ones exceed MySQL's 64-char limit.
            $table->index(['student_tel', 'course_id', 'block_type', 'is_approved'], 'sab_cycle_key_idx');
            $table->index(['block_type', 'is_approved'], 'sab_type_approved_idx');
            $table->index('blocked_at', 'sab_blocked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_block');
    }
};
