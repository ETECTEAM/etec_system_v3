<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the single `status` string column with four boolean flags
 * (present/absent/permission/late). A row with all four false is the
 * "pending" state - status had no DB default either, so this preserves
 * that behavior without a fifth column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->boolean('present')->default(false)->after('status');
            $table->boolean('absent')->default(false)->after('present');
            $table->boolean('permission')->default(false)->after('absent');
            $table->boolean('late')->default(false)->after('permission');
        });

        foreach (['present', 'absent', 'permission', 'late'] as $status) {
            DB::table('student_attendances')->where('status', $status)->update([$status => true]);
        }

        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropIndex('student_attendances_status_index');
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->after('permission');
        });

        foreach (['present', 'absent', 'permission', 'late'] as $status) {
            DB::table('student_attendances')->where($status, true)->update(['status' => $status]);
        }

        Schema::table('student_attendances', function (Blueprint $table) {
            $table->index('status');
            $table->dropColumn(['present', 'absent', 'permission', 'late']);
        });
    }
};
