<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approve After Permission (see docs/instructor-attendance-block-approve-after-permission.md):
 *
 * - unblock_reason_type  what the INSTRUCTOR claimed when they asked to track again
 *   (general / permission). Null until a request exists.
 * - reviewed_reason_type  what the ADMIN actually approved the block under. Plain
 *   Approve records 'general' and backfills only the triggering session; Approve
 *   After Permission records 'permission' and backfills every stuck session.
 *
 * The class_sessions index on (status, study_class_id, session_date) serves the
 * all-sessions sweep query — status IN (pre_attendance, partial) AND date <= blocked_at
 * for one instructor's classes. No single existing index covers that access path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_attendance_blocks', function (Blueprint $table) {
            $table->enum('unblock_reason_type', ['general', 'permission'])->nullable()->after('reason');
            $table->enum('reviewed_reason_type', ['general', 'permission'])->nullable()->after('unblock_reason_type');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->index(['status', 'study_class_id', 'session_date'], 'class_sessions_status_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropIndex('class_sessions_status_class_date_idx');
        });

        Schema::table('instructor_attendance_blocks', function (Blueprint $table) {
            $table->dropColumn(['unblock_reason_type', 'reviewed_reason_type']);
        });
    }
};