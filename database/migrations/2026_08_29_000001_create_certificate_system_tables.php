<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_class_free', function (Blueprint $table): void {
            $table->id();
            $table->string('student_name', 100);
            $table->string('course', 100);
            $table->date('end_date');
            $table->string('status', 20)->default('done');
            $table->string('certificate_code', 50)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('course');
            $table->index('certificate_code');
        });

        Schema::create('course_custom', function (Blueprint $table): void {
            $table->id();
            $table->string('course_name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('course_custom_normal', function (Blueprint $table): void {
            $table->id();
            $table->string('course_name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('student_certificate_normal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->string('certificate_type', 30)->default('normal');
            $table->string('student_name', 100);
            $table->string('course', 100);
            $table->string('granted_date', 50);
            $table->string('certificate_id', 50);
            $table->timestamps();

            $table->index(['study_class_id', 'certificate_type']);
            $table->index(['student_id', 'study_class_id', 'certificate_type'], 'student_certificate_normal_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_certificate_normal');
        Schema::dropIfExists('course_custom_normal');
        Schema::dropIfExists('course_custom');
        Schema::dropIfExists('certificate_class_free');
    }
};
