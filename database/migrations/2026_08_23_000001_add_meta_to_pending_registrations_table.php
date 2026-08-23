<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Carries the candidate classes (2-week rule, see
// RegisterStudentForSchedule::findEligibleClassesForAdmin()) captured at the
// moment the registration was parked, so admins resolving it see the same
// options that were valid when the student registered.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
