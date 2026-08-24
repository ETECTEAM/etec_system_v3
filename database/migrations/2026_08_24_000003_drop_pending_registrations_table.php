<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dead table: RegisterStudentForSchedule now parks an unschedulable
// registration as a classless StudentEnrollment (see
// no_room_and_instructor/no_instructor/no_room and the
// add_no_room_or_instructor_flags migration) instead of a row here, and
// AssignPendingStudentToClass - the only thing that ever resolved a row in
// this table - was removed in the same change.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pending_registrations');
    }

    public function down(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('time_id')->constrained('times')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }
};
