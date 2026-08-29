<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained('student_attendances')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('previous_status', 20);
            $table->string('new_status', 20);
            $table->text('reason');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'attendance_id'], 'attendance_adjustments_student_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};
