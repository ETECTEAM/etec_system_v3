<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->string('headline')->nullable()->after('status');
            $table->text('bio')->nullable()->after('headline');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('gender');
            $table->string('telegram')->nullable()->after('address');
            $table->string('linkedin')->nullable()->after('telegram');
            $table->string('github')->nullable()->after('linkedin');
            $table->string('portfolio_url')->nullable()->after('github');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->dropColumn([
                'headline',
                'bio',
                'date_of_birth',
                'gender',
                'address',
                'telegram',
                'linkedin',
                'github',
                'portfolio_url',
            ]);
        });
    }
};
