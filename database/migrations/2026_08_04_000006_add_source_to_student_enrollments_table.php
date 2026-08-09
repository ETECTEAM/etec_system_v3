<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Null means created from the backend (admin/instructor); 'public_website'
            // marks enrollments a visitor created themselves via the public /classes page.
            $table->string('source', 30)->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
