<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// scope_type/scope_id were meant for per-class or per-instructor overrides of
// grading settings, but nothing ever writes a non-null scope - every read
// path (setting(), AttendanceSettingsController::edit) filters
// whereNull('scope_type'), so the columns are dead weight.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_settings', function (Blueprint $table): void {
            $table->dropColumn(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::table('grading_settings', function (Blueprint $table): void {
            $table->string('scope_type')->nullable()->after('group');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
        });
    }
};
