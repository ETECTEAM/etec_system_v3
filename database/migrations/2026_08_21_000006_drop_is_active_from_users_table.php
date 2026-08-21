<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// is_active duplicated `status` as a second source of truth for account
// state, but EnsureAccountIsActive (the only real gate) has always read
// `status` - is_active was written by UserApprovalService and never read
// back anywhere, so it could only drift, never enforce anything.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('password');
        });
    }
};
