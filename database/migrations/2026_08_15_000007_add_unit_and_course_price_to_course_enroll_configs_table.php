<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0)->after('start_date');
            $table->decimal('course_price', 10, 2)->default(0)->after('unit_price');
            // 'unit' or 'course' - which of the two columns above is the
            // actually-charged price. Kept as its own column (not inferred)
            // so the choice survives even if unit_price and course_price
            // later happen to match.
            $table->string('selected_price_type', 10)->default('course')->after('course_price');
        });

        // Both new columns start equal to the existing price, so the
        // resolved price (see CourseEnrollConfig::resolvedPrice()) is
        // identical to today's value regardless of which type is selected -
        // this migration changes no receipt or enrollment amount.
        DB::table('course_enroll_configs')->update([
            'unit_price' => DB::raw('price'),
            'course_price' => DB::raw('price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'course_price', 'selected_price_type']);
        });
    }
};
