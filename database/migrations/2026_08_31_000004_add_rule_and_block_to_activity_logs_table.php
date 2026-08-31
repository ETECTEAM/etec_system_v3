<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * activity_logs is the shared office audit trail (originally official-leave only).
 * The absence-block feature reuses it: rows link to an attendance_rules row or a
 * student_attendance_block row instead of a leave. All three fk columns stay
 * nullable so any one audited subject is enough.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('rule_id')->nullable()->after('leave_id')
                ->constrained('attendance_rules')->nullOnDelete();
            $table->foreignId('block_id')->nullable()->after('rule_id')
                ->constrained('student_attendance_block')->nullOnDelete();

            $table->index('rule_id');
            $table->index('block_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rule_id');
            $table->dropConstrainedForeignId('block_id');
        });
    }
};
