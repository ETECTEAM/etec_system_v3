<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an instructor_availabilities row came from:
 *   'schedule' — generated from the instructor's WorkSchedule
 *                (InstructorProfileService::generateInstructorAvailabilities);
 *                wiped and rebuilt on every profile save.
 *   'admin'    — an admin / super_admin opened this slot manually from the
 *                Instructor Busy Time grid; must survive schedule regeneration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_availabilities', function (Blueprint $table) {
            $table->string('source', 20)->default('schedule')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_availabilities', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
