<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// RegisterStudentForSchedule now parks a registration it can't slot into a
// class as a classless StudentEnrollment (enrollment_status 'unassigned')
// instead of a separate pending_registrations row, so study_class_id must
// allow NULL and the row needs to say which resource was missing. MySQL
// permits NULL through an existing FK without dropping/recreating it, so
// this is a plain column-nullability change, not a constraint swap.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->boolean('no_room_and_instructor')->default(false)->after('source');
            $table->boolean('no_instructor')->default(false)->after('no_room_and_instructor');
            $table->boolean('no_room')->default(false)->after('no_instructor');
        });

        DB::statement('ALTER TABLE student_enrollments MODIFY study_class_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE student_enrollments MODIFY study_class_id BIGINT UNSIGNED NOT NULL');

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropColumn(['no_room_and_instructor', 'no_instructor', 'no_room']);
        });
    }
};
