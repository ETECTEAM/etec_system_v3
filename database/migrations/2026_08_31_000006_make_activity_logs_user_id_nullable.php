<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absence-block rows can be raised by the system (auto-record cron, a QR
 * submission) with no acting user, so activity_logs.user_id must allow null.
 * The official-leave flows always pass a user and are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Adjust nullability only - leaving the existing user_id foreign key intact.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
