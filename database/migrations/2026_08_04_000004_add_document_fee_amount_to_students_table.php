<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('document_fee_amount', 12, 2)->nullable()->after('fee_amount');
        });

        // Existing rows were saved with a flat $5 document fee baked into
        // fee_amount (RegisterStudent used to add it automatically).
        DB::table('students')->whereNotNull('fee_amount')->update([
            'document_fee_amount' => 5,
            'fee_amount' => DB::raw('GREATEST(fee_amount - 5, 0)'),
        ]);
    }

    public function down(): void
    {
        DB::table('students')->whereNotNull('fee_amount')->update([
            'fee_amount' => DB::raw('fee_amount + COALESCE(document_fee_amount, 0)'),
        ]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('document_fee_amount');
        });
    }
};
