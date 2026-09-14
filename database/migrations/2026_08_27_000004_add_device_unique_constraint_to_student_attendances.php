<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->unique(['study_class_id', 'attendance_date', 'device_identifier'], 'student_attendance_unique_device_day');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropUnique('student_attendance_unique_device_day');
        });
    }
};
