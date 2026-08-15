<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by unit_price/course_price + selected_price_type (see
        // the previous migration) - every reader was moved over to
        // CourseEnrollConfig::resolvedPrice() before this ran, so nothing
        // still depends on this column.
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('start_date');
        });
    }
};
