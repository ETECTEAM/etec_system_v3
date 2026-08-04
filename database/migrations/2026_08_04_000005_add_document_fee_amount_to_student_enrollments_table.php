<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->decimal('document_fee_amount', 12, 2)->default(0)->after('fee_amount');
        });

        // Existing rows snapshotted study_classes.price, which had a flat $5
        // document fee baked in at the time. Split it back out.
        DB::table('student_enrollments')->update([
            'document_fee_amount' => 5,
            'fee_amount' => DB::raw('GREATEST(fee_amount - 5, 0)'),
        ]);
    }

    public function down(): void
    {
        DB::table('student_enrollments')->update([
            'fee_amount' => DB::raw('fee_amount + document_fee_amount'),
        ]);

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('document_fee_amount');
        });
    }
};
