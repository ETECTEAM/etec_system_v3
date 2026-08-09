<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table) {
            $table->decimal('document_price', 12, 2)->default(0)->after('price');
        });

        // Existing rows were saved with a flat $5 document fee baked into `price`
        // (SaveStudyClassRequest used to add it automatically). Split it back out
        // so historical totals (price + document_price) stay the same.
        DB::table('study_classes')->update([
            'document_price' => 5,
            'price' => DB::raw('GREATEST(price - 5, 0)'),
        ]);
    }

    public function down(): void
    {
        DB::table('study_classes')->update([
            'price' => DB::raw('price + document_price'),
        ]);

        Schema::table('study_classes', function (Blueprint $table) {
            $table->dropColumn('document_price');
        });
    }
};
