<?php

use App\Models\StudyClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public QR join link used to be built from the class slug - the course title plus the
     * creation second (basic-it-20260919232902) - which anyone can guess. join_token is a random,
     * unguessable stand-in: the link is now /join-class/{join_token}.
     *
     * Every existing class gets its own token so it keeps a working (new) link. The old slug links
     * stop opening anything - that is the point - so a QR already on screen or printed has to be
     * shown again.
     */
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->string('join_token', 64)->nullable()->unique()->after('slug');
        });

        // Plain updates: only the token changes - no model events, no updated_at bump. chunkById
        // because the rows being filled drop out of the whereNull() as we go.
        DB::table('study_classes')
            ->whereNull('join_token')
            ->select('id')
            ->chunkById(200, function ($classes): void {
                foreach ($classes as $class) {
                    DB::table('study_classes')->where('id', $class->id)->update([
                        'join_token' => StudyClass::uniqueJoinToken(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->dropUnique(['join_token']);
            $table->dropColumn('join_token');
        });
    }
};
