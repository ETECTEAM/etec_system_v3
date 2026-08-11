<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->decimal('document_price', 10, 2)->default(5)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('course_enroll_configs', function (Blueprint $table) {
            $table->dropColumn('document_price');
        });
    }
};
