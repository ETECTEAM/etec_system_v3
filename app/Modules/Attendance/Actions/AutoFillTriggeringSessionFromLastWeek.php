<?php

namespace App\Modules\Attendance\Actions;

use App\Models\User;

/**
 * Fills the session that triggered an instructor attendance block, run once when
 * an admin approves that block as a plain miss (see
 * docs/instructor-attendance-block-proposal.md, "The triggering session", and
 * docs/instructor-attendance-block-approve-after-permission.md). A retroactive manual
 * entry for this specific class can't be trusted - the instructor who missed it is the
 * one being asked, after the fact, whether students were present - so the system fills
 * it from last week's record instead of letting the instructor re-track it.
 *
 * The whole body now lives in FillSessionFromLastWeek so the Approve After Permission
 * path (BackfillAllMissedSessionsFromLastWeek) shares the exact same per-session logic
 * instead of duplicating it. This class keeps its historic signature so the plain
 * Approve path is untouched.
 */
class AutoFillTriggeringSessionFromLastWeek
{
    public function __construct(
        private readonly FillSessionFromLastWeek $fill,
    ) {}

    public function handle(int $sessionId, User $actor): void
    {
        $this->fill->handle($sessionId, $actor);
    }
}