<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_permissions', function (Blueprint $table): void {
            $table->text('note')->nullable()->after('reason');
        });

        Schema::table('attendance_adjustments', function (Blueprint $table): void {
            $table->string('action', 80)->nullable()->after('changed_by');
            $table->foreignId('study_class_id')->nullable()->after('student_id')->constrained('study_classes')->nullOnDelete();
            $table->foreignId('target_study_class_id')->nullable()->after('study_class_id')->constrained('study_classes')->nullOnDelete();
            $table->date('effective_date')->nullable()->after('reason');
            $table->date('start_date')->nullable()->after('effective_date');
            $table->date('end_date')->nullable()->after('start_date');
            $table->json('before_payload')->nullable()->after('ip_address');
            $table->json('after_payload')->nullable()->after('before_payload');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_adjustments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('target_study_class_id');
            $table->dropConstrainedForeignId('study_class_id');
            $table->dropColumn([
                'action',
                'effective_date',
                'start_date',
                'end_date',
                'before_payload',
                'after_payload',
            ]);
        });

        Schema::table('student_permissions', function (Blueprint $table): void {
            $table->dropColumn('note');
        });
    }
};
