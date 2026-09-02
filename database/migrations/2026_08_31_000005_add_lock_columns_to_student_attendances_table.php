<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an open absence/hard-lock block covers a student, their attendance row for
 * that day is forced to status='absent' with locked=true and a human reason.
 * The absence-block evaluator owns these columns; instructor / QR / auto-record
 * writes can set them but never clear them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->boolean('locked')->default(false)->after('status');
            $table->string('lock_reason')->nullable()->after('locked');
            $table->foreignId('locked_block_id')->nullable()->after('lock_reason')
                ->constrained('student_attendance_block')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_block_id');
            $table->dropColumn(['locked', 'lock_reason']);
        });
    }
};
