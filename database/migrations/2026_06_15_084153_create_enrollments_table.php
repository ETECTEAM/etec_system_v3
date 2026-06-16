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
      Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            // Student who registers
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // Selected class
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            // Enrollment status
            $table->enum('status', [
                'active',
                'cancelled',
                'completed'
            ])->default('active');

            // Registration date
            $table->date('enrollment_date')->nullable();

            // Optional notes
            $table->text('note')->nullable();

            $table->timestamps();

            // Prevent same student registering same class twice
            $table->unique(['student_id', 'class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
