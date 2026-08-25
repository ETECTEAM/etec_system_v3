<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Official leave requested by a student through the office QR flow (or reviewed from
 * history). Only status='approved' rows excuse attendance — pending does not. A null
 * study_class_id covers every class the student is enrolled in, mirroring
 * student_permissions' scoping rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_leaves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('study_class_id')
                ->nullable()
                ->constrained('study_classes')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');

            $table->string('status', 20)->default('pending')->index();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('rejection_note')->nullable();

            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_note')->nullable();

            // The QR session this request came from (null for office-created records).
            $table->foreignId('leave_request_session_id')
                ->nullable()
                ->constrained('leave_request_sessions')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_leaves');
    }
};
