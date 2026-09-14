<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable - existing rows stay schedule_id = NULL and keep
        // resolving exactly as before.
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->foreignId('schedule_id')
                ->nullable()
                ->after('course_id')
                ->constrained('schedules')
                ->onDelete('cascade');

            $table->unique(['course_id', 'schedule_id', 'time_id']);
            $table->dropUnique(['course_id', 'time_id']);
        });
    }

    public function down(): void
    {
        // Order matters: MySQL needs an index starting with course_id to
        // exist at all times (error 1553 otherwise), so add the old unique
        // back before dropping the one currently backing that FK.
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->unique(['course_id', 'time_id']);
        });

        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'schedule_id', 'time_id']);
            $table->dropConstrainedForeignId('schedule_id');
        });
    }
};
