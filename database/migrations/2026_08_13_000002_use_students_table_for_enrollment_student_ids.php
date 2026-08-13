<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        $this->makeStudentUserNullable();

        DB::table('student_enrollments')
            ->join('students', 'students.user_id', '=', 'student_enrollments.student_id')
            ->update(['student_enrollments.student_id' => DB::raw('students.id')]);

        DB::table('student_attendances')
            ->join('students', 'students.user_id', '=', 'student_attendances.student_id')
            ->update(['student_attendances.student_id' => DB::raw('students.id')]);

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
        });

        DB::table('student_enrollments')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->whereNotNull('students.user_id')
            ->update(['student_enrollments.student_id' => DB::raw('students.user_id')]);

        DB::table('student_attendances')
            ->join('students', 'students.id', '=', 'student_attendances.student_id')
            ->whereNotNull('students.user_id')
            ->update(['student_attendances.student_id' => DB::raw('students.user_id')]);

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function makeStudentUserNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('alter table students alter column user_id drop not null');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('alter table students modify user_id bigint unsigned null');
        }
    }
};
