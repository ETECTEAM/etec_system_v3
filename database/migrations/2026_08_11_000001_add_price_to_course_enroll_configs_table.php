<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
