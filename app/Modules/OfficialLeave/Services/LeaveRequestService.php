<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\LeaveRequestSession;
use App\Models\OfficialLeave;
use App\Models\OfficialLeaveSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LeaveRequestService
{
    public function searchStudents(string $search)
    {
        return Student::with(['user', 'enrollments.scheduleClass.course', 'enrollments.scheduleClass.term'])
            ->where(function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();
    }

    public function generateQrToken(int $studentId, int $createdBy): array
    {
        DB::beginTransaction();

        try {
            // Invalidate any existing unused sessions for this student
            LeaveRequestSession::where('student_id', $studentId)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $token = Str::random(64);
            $ttlMinutes = (int) OfficialLeaveSetting::where('key', 'qr_token_ttl_minutes')->value('value') ?? 15;

            $session = LeaveRequestSession::create([
                'student_id' => $studentId,
                'created_by' => $createdBy,
                'token_hash' => Hash::make($token),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            DB::commit();

            return [
                'session' => $session,
                'token' => $token,
                'url' => route('leave.form', ['token' => $token]),
                'expires_at' => $session->expires_at->toIso8601String(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function validateToken(string $token): ?LeaveRequestSession
    {
        $sessions = LeaveRequestSession::where('expires_at', '>', now())
            ->whereNull('used_at')
            ->get();

        foreach ($sessions as $session) {
            if (Hash::check($token, $session->token_hash)) {
                return $session;
            }
        }

        return null;
    }

    public function consumeToken(LeaveRequestSession $session): void
    {
        $session->update(['used_at' => now()]);
    }

    public function pollSession(int $sessionId): ?OfficialLeave
    {
        $session = LeaveRequestSession::findOrFail($sessionId);

        return OfficialLeave::where('leave_request_session_id', $session->id)
            ->where('status', OfficialLeave::STATUS_PENDING)
            ->first();
    }

    public function getStudent(int $studentId)
    {
        return Student::with(['user', 'enrollments.scheduleClass.course', 'enrollments.scheduleClass.term'])
            ->findOrFail($studentId);
    }
}
