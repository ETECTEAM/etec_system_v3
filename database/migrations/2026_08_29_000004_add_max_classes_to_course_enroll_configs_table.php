<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            // How many live (not ended / cancelled) classes may run in this
            // course + class-type + term + time slot. NULL = no limit. Only
            // meaningful on the schedule_id-scoped availability rows (the time
            // badges on the Enroll Config page); the course-wide / time-price
            // rows leave it NULL.
            $table->unsignedSmallInteger('max_classes')->nullable()->after('document_price');
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropColumn('max_classes');
        });
    }
};
