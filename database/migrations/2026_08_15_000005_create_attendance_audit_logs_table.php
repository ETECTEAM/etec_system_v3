<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only trail of who changed an auto-recorded attendance row and what it changed
 * from/to — required whenever an instructor overrides a system-recorded session.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_attendance_id')
                ->constrained('student_attendances')
                ->cascadeOnDelete();

            $table->foreignId('changed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('from_source', 20)->nullable();
            $table->string('to_source', 20);

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audit_logs');
    }
};
