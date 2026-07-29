<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton row (id = 1) holding the account-level lockout config
        // that isn't tier-specific.
        Schema::create('login_lockout_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('reset_after_hours')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_lockout_settings');
    }
};
