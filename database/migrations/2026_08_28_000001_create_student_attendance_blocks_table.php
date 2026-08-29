<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('block_type', 20);
            $table->string('status', 20)->default('pending');
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_comment')->nullable();
            $table->timestamp('cycle_started_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id', 'block_type', 'status'], 'student_attendance_blocks_lookup');
            $table->index(['status', 'blocked_at'], 'student_attendance_blocks_status_blocked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_blocks');
    }
};
