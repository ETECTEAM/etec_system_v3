<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per account that has ever had a lockout event. Replaces
        // the cache-only ban/offense tracking so admins can list and
        // manually unblock currently-banned accounts.
        Schema::create('login_lockouts', function (Blueprint $table) {
            $table->id();
            $table->string('login')->unique();
            $table->unsignedInteger('offense_number')->default(0);
            $table->timestamp('last_offense_at')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->boolean('is_hard_block')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_lockouts');
    }
};
