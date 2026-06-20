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
        Schema::create('class_list', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('course_id')->nullable()
                ->constrained('courses')->nullOnDelete();

            $table->foreignId('lesson_id')->nullable()
                ->constrained('lessons')->nullOnDelete();

            $table->foreignId('term_id')->nullable()
                ->constrained('terms')->nullOnDelete();

            $table->foreignId('time_id')->nullable()
                ->constrained('times')->nullOnDelete();

            $table->foreignId('building_id')->nullable()
                ->constrained('buildings')->nullOnDelete();

            $table->foreignId('floor_id')->nullable()
                ->constrained('floors')->nullOnDelete();

            $table->foreignId('room_id')->nullable()
                ->constrained('rooms')->nullOnDelete();

            $table->foreignId('class_type_id')->nullable()
                ->constrained('class_type')->nullOnDelete();

            $table->integer('student_count')->default(0);
            $table->string('status')->default('progress');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_list');
    }
};
