<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('full_name')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('full_name');
            });
        }
    }
};
