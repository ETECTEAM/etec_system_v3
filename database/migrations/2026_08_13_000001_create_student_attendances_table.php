<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tracked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['study_class_id', 'student_enrollment_id', 'attendance_date'], 'student_attendance_unique_day');
            $table->index(['study_class_id', 'attendance_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
