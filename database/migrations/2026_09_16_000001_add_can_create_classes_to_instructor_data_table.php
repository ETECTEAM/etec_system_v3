<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->boolean('can_create_classes')->default(false)->after('available_for_class');
        });

        // Every instructor that already exists today has been implicitly
        // trusted (they're already teaching) - only instructors created after
        // this migration start out unapproved and need an admin to flip this
        // on, separate from the general create-classes permission that stays
        // on the instructor role for everyone.
        DB::table('instructor_data')->update(['can_create_classes' => true]);
    }

    public function down(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->dropColumn('can_create_classes');
        });
    }
};
