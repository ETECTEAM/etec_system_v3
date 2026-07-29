<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_lockout_settings', function (Blueprint $table) {
            // How many wrong attempts are free before the very first lockout
            // trips. After that first lockout, every single wrong attempt
            // escalates immediately - this only gates offense 1.
            $table->unsignedInteger('free_attempts')->default(5)->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('login_lockout_settings', function (Blueprint $table) {
            $table->dropColumn('free_attempts');
        });
    }
};
