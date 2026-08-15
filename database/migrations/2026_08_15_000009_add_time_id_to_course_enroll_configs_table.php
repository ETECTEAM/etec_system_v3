<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each course can now offer several enrollment schedules - one row per
        // time slot (from the times table), each with its own start date,
        // prices, price-to-use and status. time_id = NULL is the course's
        // default/general config, which is what the pre-existing single row
        // per course becomes, so no data migration is needed.
        //
        // Order matters on MySQL: the course_id FK needs an index, so the new
        // (course_id, time_id) unique is added before the old course_id unique
        // is dropped - the composite index then keeps satisfying the FK.
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->foreignId('time_id')
                ->nullable()
                ->after('course_id')
                ->constrained('times')
                ->onDelete('set null');

            $table->unique(['course_id', 'time_id']);
            $table->dropUnique(['course_id']);
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'time_id']);
            $table->dropConstrainedForeignId('time_id');
            $table->unique('course_id');
        });
    }
};
