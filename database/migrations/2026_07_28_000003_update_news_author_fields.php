<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            if (! Schema::hasColumn('news', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('slug')->constrained()->nullOnDelete();
            }

            if (Schema::hasColumn('news', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('news', 'author')) {
                $table->dropColumn('author');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            if (! Schema::hasColumn('news', 'category')) {
                $table->string('category')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('news', 'author')) {
                $table->string('author')->nullable()->after('category');
            }

            if (Schema::hasColumn('news', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
