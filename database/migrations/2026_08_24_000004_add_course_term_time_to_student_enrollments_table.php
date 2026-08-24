<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A classless (enrollment_status 'unassigned') row has no study_class_id to
// read the course/term/time the student asked for from, so
// RegisterStudentForSchedule snapshots them here at registration time. Once
// a class is assigned these are just left as-is (harmless, and useful as a
// record of the original request) - studyClass's own course/term/time take
// priority for display whenever a class exists.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->after('study_class_id')->constrained('courses')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->after('course_id')->constrained('terms')->nullOnDelete();
            $table->foreignId('time_id')->nullable()->after('term_id')->constrained('times')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('term_id');
            $table->dropConstrainedForeignId('time_id');
        });
    }
};
