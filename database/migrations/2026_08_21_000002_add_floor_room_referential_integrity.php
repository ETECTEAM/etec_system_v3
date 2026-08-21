<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// rooms.floor_id had no FK at all (orphan rooms survive a floor delete with a
// stale id), and neither floors nor rooms had a DB-level uniqueness guard, so
// concurrent bulk inserts (BuildingController::bulkStoreFloor/RoomController::
// bulkStore) could create duplicate rows the app-layer check missed under a race.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreign('floor_id')->references('id')->on('floors')->nullOnDelete();
            $table->unique(['floor_id', 'room_number']);
        });

        Schema::table('floors', function (Blueprint $table): void {
            $table->unique(['building_id', 'name']);
        });
    }

    public function down(): void
    {
        // The composite unique index is currently the only index covering its
        // leading FK column, so MySQL refuses to drop it outright - a plain
        // single-column index has to exist first to keep backing the
        // pre-existing building_id FK (which this migration doesn't own and
        // must leave intact), so it's added but never dropped back out.
        Schema::table('floors', function (Blueprint $table): void {
            $table->index('building_id');
            $table->dropUnique(['building_id', 'name']);
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->index('floor_id');
            $table->dropUnique(['floor_id', 'room_number']);
            $table->dropForeign(['floor_id']);
            $table->dropIndex(['floor_id']);
        });
    }
};
