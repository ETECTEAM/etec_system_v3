<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A class can be shared by two instructors who each teach part of it — e.g. a Basic IT
 * class where one instructor takes the Code lessons on Mon & Tue and another takes the
 * Network lessons on Wed & Thu. Each row is one instructor's slot on the class: their
 * own term (study days) and time, which is what their dashboard and attendance use.
 * study_classes.teacher_id stays the class owner; a row here exists for them too once
 * the class is shared, so both schedules are defined in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_class_instructors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_class_id')
                ->constrained('study_classes')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // This instructor's own schedule for the class. Null falls back to the class's.
            $table->foreignId('term_id')
                ->nullable()
                ->constrained('terms')
                ->nullOnDelete();

            $table->foreignId('time_id')
                ->nullable()
                ->constrained('times')
                ->nullOnDelete();

            // What this instructor teaches on the class, e.g. "Code" or "Network".
            $table->string('subject')->nullable();

            $table->timestamps();

            $table->unique(['study_class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_class_instructors');
    }
};
