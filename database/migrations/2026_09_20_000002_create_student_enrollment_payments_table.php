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
        // A first run on MySQL created the table and then failed on the over-long index name below;
        // DDL isn't rolled back there, so a retry must build on that leftover table instead of failing.
        if (! Schema::hasTable('student_enrollment_payments')) {
            $this->createTable();
        }

        Schema::table('student_enrollment_payments', function (Blueprint $table) {
            // Explicit name: the generated one is 68 characters, over MySQL's 64-character limit.
            if (! Schema::hasIndex('student_enrollment_payments', 'sep_enrollment_payment_date_index')) {
                $table->index(['student_enrollment_id', 'payment_date'], 'sep_enrollment_payment_date_index');
            }

            if (! Schema::hasIndex('student_enrollment_payments', 'student_enrollment_payments_payment_status_index')) {
                $table->index('payment_status');
            }

            if (! Schema::hasIndex('student_enrollment_payments', 'student_enrollment_payments_payment_date_index')) {
                $table->index('payment_date');
            }
        });
    }

    private function createTable(): void
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_payments');
    }
};
