<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only trail for every mutating action in the official-leave feature:
 * who did it, to which leave, what changed, and from where. Rows survive the
 * leave being deleted (official_leave_id nulls out on delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action', 50)->index();

            $table->foreignId('official_leave_id')
                ->nullable()
                ->constrained('official_leaves')
                ->nullOnDelete();

            $table->json('before')->nullable();
            $table->json('after')->nullable();

            $table->string('ip', 45)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
