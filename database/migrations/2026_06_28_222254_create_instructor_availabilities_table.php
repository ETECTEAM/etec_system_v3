<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructor_data')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->string('employment_type');
            $table->string('shift_group');
            $table->string('period');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['instructor_id', 'day_of_week']);
            $table->index('shift_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_availabilities');
    }
};
