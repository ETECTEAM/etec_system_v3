<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps a course track to the Class Type it is taught as, so Enroll Config
     * can show only that Class Type's Schedule Management schedules/times for
     * the track's courses. Nullable: a NULL track keeps the previous behaviour
     * of offering the default Physical / Scholarship / Online schedules.
     */
    public function up(): void
    {
        Schema::table('course_tracks', function (Blueprint $table) {
            $table->foreignId('class_type_id')
                ->nullable()
                ->after('sub_category_id')
                ->constrained('class_type', 'class_type_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_tracks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_type_id');
        });
    }
};
