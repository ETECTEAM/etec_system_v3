<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            // Class selected by the student
            $table->foreignId('study_class_id')
                ->constrained('study_classes')
                ->cascadeOnDelete();

            // Student comes from users table
            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * pending, active, completed, cancelled
             */
            $table->string('enrollment_status', 20)
                ->default('active');

            /*
             * unpaid, partial, paid
             */
            $table->string('payment_status', 20)
                ->default('unpaid');

            /*
             * Save the class price at enrollment time.
             * This protects old enrollments if class price changes later.
             */
            $table->decimal('fee_amount', 12, 2)
                ->default(0);

            $table->decimal('amount_paid', 12, 2)
                ->default(0);

            $table->timestamp('enrolled_at')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();

            // One student cannot enroll twice in the same class
            $table->unique([
                'study_class_id',
                'student_id',
            ]);

            $table->index('enrollment_status');
            $table->index('payment_status');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
