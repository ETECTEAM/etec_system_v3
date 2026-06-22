<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing accounts use active/pending/rejected strings. Convert active
        // accounts to 1 and every non-active account to 0 before changing type.
        DB::table('users')->where('status', 'active')->update(['status' => '1']);
        DB::table('users')->where('status', '!=', '1')->update(['status' => '0']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 30)->default('admin')->change();
            $table->boolean('status')->default(true)->change();
            $table->string('avatar')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->index('role');
            $table->index('status');
            $table->index(['role', 'status']);
            $table->index(['role', 'status', 'created_at']);
        });

        Schema::create('student_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('student_code')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('full_name_kh')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('full_name');
            $table->index('full_name_kh');
            $table->index('phone');
            $table->index('email');
            $table->index('gender');
            $table->index('class_id');
            $table->index('status');
            $table->index('parent_phone');
            $table->index(['class_id', 'status']);
            $table->index(['gender', 'status']);
            $table->index(['student_code', 'class_id']);
            $table->index(['full_name', 'phone']);
        });

        Schema::create('instructor_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('instructor_code')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('full_name_kh')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('specialization')->nullable();
            $table->string('employment_type', 20);
            $table->string('shift_preference', 30);
            $table->boolean('available_for_class')->default(true);
            $table->date('hire_date')->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('full_name');
            $table->index('full_name_kh');
            $table->index('phone');
            $table->index('email');
            $table->index('gender');
            $table->index('specialization');
            $table->index('employment_type');
            $table->index('shift_preference');
            $table->index('available_for_class');
            $table->index('status');
            $table->index(['employment_type', 'shift_preference']);
            $table->index(['available_for_class', 'employment_type']);
            $table->index(['available_for_class', 'shift_preference']);
            $table->index(['employment_type', 'shift_preference', 'available_for_class'], 'instructor_emp_shift_availability_idx');
            $table->index(['instructor_code', 'employment_type']);
            $table->index(['full_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_data');
        Schema::dropIfExists('student_data');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['role', 'status', 'created_at']);
            $table->dropColumn(['avatar', 'last_login_at']);
            $table->enum('role', ['admin', 'instructor'])->default('instructor')->change();
            $table->string('status', 20)->default('active')->change();
        });
    }
};
