<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// InstructorClassService::saveTeams validates each submitted team_name is
// non-empty but never checks for duplicates within the same save, so two
// teams named "Team A" could land in the same class with nothing to tell
// them apart. Promotes the existing plain index to a unique constraint.
//
// MySQL refuses to drop group_id_team_name_index directly because it's the
// index currently backing the group_id foreign key - a standalone group_id
// index has to exist first so the FK has something else to lean on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->index('group_id');
            $table->dropIndex(['group_id', 'team_name']);
            $table->unique(['group_id', 'team_name']);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropUnique(['group_id', 'team_name']);
            $table->index(['group_id', 'team_name']);
            $table->dropIndex(['group_id']);
        });
    }
};
