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
        Schema::table('holidays', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->after('id')->index();
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('description')->nullable()->after('end_date');
        });

        DB::table('holidays')
            ->orderBy('id')
            ->select(['id', 'date'])
            ->get()
            ->each(function ($holiday): void {
                DB::table('holidays')
                    ->where('id', $holiday->id)
                    ->update([
                        'group_id' => (string) Str::uuid(),
                        'start_date' => $holiday->date,
                        'end_date' => $holiday->date,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn(['group_id', 'start_date', 'end_date', 'description']);
        });
    }
};
