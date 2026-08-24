<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Display order for the public student-register course list - lower
        // numbers show first (1 = top). Null falls back to the old title sort.
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('enroll_order')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('enroll_order');
        });
    }
};
