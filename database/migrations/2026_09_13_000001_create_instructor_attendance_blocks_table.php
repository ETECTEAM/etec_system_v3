<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blocks an instructor's ability to track attendance on ANY class after they miss it on one
 * (see docs/instructor-attendance-block-proposal.md). One row per instructor while a block is
 * outstanding - a new incident refreshes the existing row rather than creating a duplicate.
 *
 * "Blocking" statuses (anything that still stops attendance submission) = everything except
 * 'approved_unblock'. No 'rejected' state: an admin who doesn't want to unblock someone just
 * doesn't approve - there's nothing a separate reject status would add.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_attendance_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('triggered_by_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();

            $table->string('reason');
            $table->enum('status', ['active', 'pending_review', 'approved_unblock'])->default('active');

            $table->timestamp('blocked_at');
            $table->timestamp('unblock_requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['instructor_id', 'status'], 'iab_instructor_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_attendance_blocks');
    }
};
