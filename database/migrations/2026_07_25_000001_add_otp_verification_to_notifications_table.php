<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('type')->default('general')->after('message');
            $table->foreignId('otp_verification_id')->nullable()->after('type')
                ->constrained('otp_verifications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('otp_verification_id');
            $table->dropColumn('type');
        });
    }
};
