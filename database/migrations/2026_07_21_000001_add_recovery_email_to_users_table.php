<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal address (e.g. Gmail) used to deliver password-reset
            // links instead of the login/institution email.
            $table->string('recovery_email')->nullable()->after('email');
            // Flips true only once the signed verification link for the
            // current recovery_email has been clicked. Any change to
            // recovery_email resets this back to false immediately.
            $table->boolean('recovery_verified')->default(false)->after('recovery_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['recovery_email', 'recovery_verified']);
        });
    }
};
