<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SESSION_STUDENT_UNIQUE = 'student_attendance_unique_session_student';

    private const ENROLLMENT_LOOKUP_INDEX = 'student_enrollments_student_class_status_index';

    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            if (! Schema::hasIndex('student_attendances', self::SESSION_STUDENT_UNIQUE)) {
                $table->unique(['attendance_session_id', 'student_id'], self::SESSION_STUDENT_UNIQUE);
            }
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            if (! Schema::hasIndex('student_enrollments', self::ENROLLMENT_LOOKUP_INDEX)) {
                $table->index(['student_id', 'study_class_id', 'enrollment_status'], self::ENROLLMENT_LOOKUP_INDEX);
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            if (Schema::hasIndex('student_attendances', self::SESSION_STUDENT_UNIQUE)) {
                $table->dropUnique(self::SESSION_STUDENT_UNIQUE);
            }
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            if (Schema::hasIndex('student_enrollments', self::ENROLLMENT_LOOKUP_INDEX)) {
                $table->dropIndex(self::ENROLLMENT_LOOKUP_INDEX);
            }
        });
    }
};
