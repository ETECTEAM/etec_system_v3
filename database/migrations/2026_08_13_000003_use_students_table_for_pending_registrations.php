<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Public registration no longer creates a users row for a student (see
// RegisterStudentForSchedule), so pending_registrations.student_id can no
// longer point at users. Mirrors the student_enrollments/student_attendances
// switch to students.id in 2026_08_13_000002.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        DB::table('pending_registrations')
            ->join('students', 'students.user_id', '=', 'pending_registrations.student_id')
            ->update(['pending_registrations.student_id' => DB::raw('students.id')]);

        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        DB::table('pending_registrations')
            ->join('students', 'students.id', '=', 'pending_registrations.student_id')
            ->whereNotNull('students.user_id')
            ->update(['pending_registrations.student_id' => DB::raw('students.user_id')]);

        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
