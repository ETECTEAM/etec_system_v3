<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-approved leave for a student, checked by the auto-record scheduler so a student on
 * approved leave is recorded as 'permission' rather than being auto-marked present/absent
 * for a class they were never expected at. Scope: null study_class_id covers every class
 * the student is enrolled in; a set study_class_id covers only that one.
 *
 * v1 is grant-only (an admin/instructor records an already-approved leave directly) — there
 * is no request/approve pipeline here; that would be its own feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('study_class_id')
                ->nullable()
                ->constrained('study_classes')
                ->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['student_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_permissions');
    }
};
