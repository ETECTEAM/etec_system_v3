<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->string('status', 20)->default('closed')->change();
        });
    }
};
