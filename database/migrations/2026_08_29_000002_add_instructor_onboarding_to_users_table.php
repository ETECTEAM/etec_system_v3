<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only self-registered instructors (via /instructor-register + OTP)
            // are forced through onboarding. Existing accounts default to false
            // so they are never retroactively gated.
            $table->boolean('requires_onboarding')->default(false)->after('role');
            // Set once the instructor has completed setup (employment type,
            // work schedule, specialization, and a verified recovery email).
            $table->timestamp('onboarding_completed_at')->nullable()->after('requires_onboarding');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['requires_onboarding', 'onboarding_completed_at']);
        });
    }
};
