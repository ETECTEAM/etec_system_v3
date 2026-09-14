<?php

namespace App\Modules\AbsenceBlock\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Actions\UnlockHardLock;
use Illuminate\Http\RedirectResponse;

/**
 * The one super_admin-only capability: clearing a hard lock, which also resets
 * the absence cycle for that tel + course_id.
 */
class BlacklistController extends Controller
{
    public function unlock(StudentAttendanceBlock $block, UnlockHardLock $action): RedirectResponse
    {
        $this->authorize('unlock', StudentAttendanceBlock::class);

        $action->handle($block, request()->user());

        return back()->with('success', 'Hard lock cleared. The absence cycle has been reset for this student.');
    }
}
