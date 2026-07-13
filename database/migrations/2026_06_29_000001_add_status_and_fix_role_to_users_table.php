<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('active')->after('role');
            }
        });

        DB::statement('ALTER TABLE users MODIFY role VARCHAR(50) NULL');
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','instructor') DEFAULT 'instructor'");
    }
};
