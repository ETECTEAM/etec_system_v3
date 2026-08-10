<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enroll_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->unique()
                ->constrained('courses')
                ->onDelete('cascade');

            // open, closed — whether the course is currently accepting enrollments.
            $table->string('status', 20)->default('closed');

            $table->date('start_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enroll_configs');
    }
};
