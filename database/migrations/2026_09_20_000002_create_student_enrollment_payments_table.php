<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-payment history ledger for student enrollments.
     *
     * Each deposit recorded via RecordEnrollmentDeposit appends a row here,
     * so previous payments are never overwritten and the View Enrollment
     * modal can render a complete, ordered payment history.
     */
    public function up(): void
    {
        Schema::create('student_enrollment_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_enrollment_id')
                ->constrained('student_enrollments')
                ->cascadeOnDelete();

            $table->foreignId('study_class_id')
                ->constrained('study_classes')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students');

            $table->decimal('amount', 12, 2);

            $table->string('payment_method', 50)->nullable();

            $table->date('payment_date');

            $table->string('payment_status', 20)->default('completed');

            $table->string('reference_number', 100)->nullable();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('remarks', 255)->nullable();

            $table->timestamps();

            $table->index(['student_enrollment_id', 'payment_date']);
            $table->index('payment_status');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_payments');
    }
};
