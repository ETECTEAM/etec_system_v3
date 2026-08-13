<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();

            // Points at users here; a later migration
            // (2026_08_13_000003_use_students_table_for_pending_registrations)
            // repoints this at the students table instead.
            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('term_id')
                ->constrained('terms')
                ->cascadeOnDelete();

            $table->foreignId('time_id')
                ->constrained('times')
                ->cascadeOnDelete();

            // pending -> resolved once staff manually assigns the student to a class
            $table->string('status', 20)->default('pending');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
