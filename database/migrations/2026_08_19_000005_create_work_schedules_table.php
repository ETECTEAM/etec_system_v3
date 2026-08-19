<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_schedule_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->foreignId('time_id')->constrained('times')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['work_schedule_id', 'day_of_week', 'time_id']);
        });

        Schema::table('instructor_data', function (Blueprint $table) {
            $table->foreignId('work_schedule_id')->nullable()->after('shift_template_id')->constrained('work_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->dropForeign(['work_schedule_id']);
            $table->dropColumn('work_schedule_id');
        });

        Schema::dropIfExists('work_schedule_times');
        Schema::dropIfExists('work_schedules');
    }
};
