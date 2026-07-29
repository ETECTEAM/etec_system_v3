<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_lockout_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('offense_number')->unique();
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_lockout_tiers');
    }
};
