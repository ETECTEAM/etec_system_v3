<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records who created a manual schedule block. NULL = the instructor blocked
 * their own slot from "My Availability"; a user id = an admin / super_admin
 * blocked it for them from the Instructor Busy Time grid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_schedule_blocks', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('reason')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instructor_schedule_blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
