<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('email')->nullable()->unique()->after('phone');
        });

        DB::table('students')->whereNull('email')->orderBy('id')->each(function (object $student): void {
            $localPart = Str::of($student->full_name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->value() ?: 'student';
            $candidate = "{$localPart}@etec.com";
            $suffix = 2;

            while (DB::table('students')->where('email', $candidate)->exists()) {
                $candidate = "{$localPart}-{$suffix}@etec.com";
                $suffix++;
            }

            DB::table('students')->where('id', $student->id)->update(['email' => $candidate]);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
