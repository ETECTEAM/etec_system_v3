<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fee_amount is the charged Course Price (which may be a discount). unit_price
 * snapshots the Enroll Config's list/unit price at registration time so a
 * receipt reprinted later still shows both figures even if the course's config
 * price changes afterwards. Nullable: rows created before this migration have
 * no snapshot and the receipt falls back to a single price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('fee_amount');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
