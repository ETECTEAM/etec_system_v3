<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton row (id = 1): whether instructor self-registration requires
        // OTP verification before activation. Previously a static .env value
        // (auth.otp.enabled) - moved to the database so a super_admin can
        // flip it live from the dashboard without a deploy.
        Schema::create('otp_verification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verification_settings');
    }
};
