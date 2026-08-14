<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'source' to distinguish who recorded a row (manual / auto / admin_edit).
 * attendance_date already serves as the prompt's "session_date" and tracked_by already
 * serves as "recorded_by" — both pre-existing columns, not duplicated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
