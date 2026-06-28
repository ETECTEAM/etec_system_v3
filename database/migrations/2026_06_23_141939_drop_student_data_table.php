<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('student_data');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Do not recreate student_data.
        // Project now uses students table only.
    }
};