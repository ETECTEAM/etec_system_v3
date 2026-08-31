<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numeric attendance-policy rules maintained by the school office.
 *
 * period_type selects WHICH classes a rule covers, not the counting window:
 *   both  -> every class
 *   week  -> weekday classes
 *   month -> weekend classes
 * Multiple active rules per rule_type are allowed; the newest (highest id) wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('rule_type', ['absence', 'permission']);
            $table->unsignedInteger('limit_count');
            $table->enum('period_type', ['week', 'month', 'both']);
            $table->date('start_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rule_type', 'is_active']);
            $table->index('period_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
