<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('activity_score', 5, 2)->default(0);
            $table->decimal('exam_score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique('student_enrollment_id');
            $table->index(['study_class_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scores');
    }
};
