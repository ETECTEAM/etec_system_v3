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
        Schema::create('study_classes', function (Blueprint $table) {
            $table->id();

            // Main class information
            $table->string('title');

            // Course connected to this class
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            // Current lesson displayed on the class card
            $table->foreignId('lesson_id')
                ->nullable()
                ->constrained('course_lessons')
                ->nullOnDelete();

            // Teacher comes from users table
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * We save only room_id.
             * Floor and Building will be retrieved through:
             * room -> floor -> building
             */
            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();

            /*
             * physical or online
             */
            $table->string('class_type', 20)
                ->default('physical');

            /*
             * upcoming, active, pre_end, ended, cancelled
             */
            $table->string('status', 20)
                ->default('upcoming');

            // Example: ["Monday", "Thursday"]
            $table->json('study_days');

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedInteger('capacity')
                ->default(20);

            $table->decimal('price', 12, 2)
                ->default(0);

            $table->date('enrollment_start_date')
                ->nullable();

            $table->date('start_date')
                ->nullable();

            $table->date('end_date')
                ->nullable();

            // Used when class_type is online
            //$table->string('meeting_link')
            //    ->nullable();

            $table->timestamps();

            $table->index('class_type');
            $table->index('status');
            $table->index('start_date');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('study_classes');
    }
};
