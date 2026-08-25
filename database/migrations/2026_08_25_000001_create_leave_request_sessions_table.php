<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per QR the office generates for a student's leave request. Only the SHA-256
 * hash of the token is stored — the plaintext token lives exclusively inside the signed
 * URL the student scans. used_at makes the token single-use; official_leaves carries a
 * leave_request_session_id FK back to this row so the dashboard's poll endpoint can find
 * the resulting request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // The office user who generated this QR.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_sessions');
    }
};
