<?php

namespace App\Modules\Attendance\Services;

use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use stdClass;

class AttendanceQrService
{
    public function getOrCreateTodaySession(StudyClass $studyClass, User $creator): AttendanceSession
    {
        $date = Carbon::today('Asia/Phnom_Penh')->toDateString();
        $session = AttendanceSession::query()
            ->where('study_class_id', $studyClass->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($session instanceof AttendanceSession) {
            if ($session->status === AttendanceSession::STATUS_ACTIVE && $session->expires_at?->isFuture()) {
                return $session;
            }

            return $session;
        }

        return $this->startSession($studyClass, $creator);
    }

    public function startSession(StudyClass $studyClass, User $creator): AttendanceSession
    {
        $now = Carbon::now('Asia/Phnom_Penh');
        $date = $now->toDateString();
        $token = Str::random(64);
        $expiresAt = $now->copy()->addMinutes($this->ttlMinutes($studyClass));

        return DB::transaction(function () use ($studyClass, $creator, $date, $now, $token, $expiresAt): AttendanceSession {
            $session = AttendanceSession::query()
                ->where('study_class_id', $studyClass->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                return AttendanceSession::create([
                    'study_class_id' => $studyClass->id,
                    'qr_token' => $token,
                    'attendance_date' => $date,
                    'started_at' => $now,
                    'expires_at' => $expiresAt,
                    'status' => AttendanceSession::STATUS_ACTIVE,
                    'created_by' => $creator->id,
                ]);
            }

            $session->update([
                'qr_token' => $token,
                'started_at' => $now,
                'expires_at' => $expiresAt,
                'status' => AttendanceSession::STATUS_ACTIVE,
                'created_by' => $creator->id,
                'stopped_by' => null,
                'stopped_at' => null,
            ]);

            return $session->refresh();
        });
    }

    public function stopSession(AttendanceSession $session, User $user): AttendanceSession
    {
        $session->update([
            'status' => AttendanceSession::STATUS_STOPPED,
            'stopped_by' => $user->id,
            'stopped_at' => now(),
        ]);

        return $session->refresh();
    }

    public function resolveSession(string $token): ?AttendanceSession
    {
        $session = AttendanceSession::query()
            ->where('qr_token', $token)
            ->first();

        if (! $session) {
            return null;
        }

        if ($session->status === AttendanceSession::STATUS_ACTIVE && $session->expires_at !== null && $session->expires_at->isPast()) {
            $session->update(['status' => AttendanceSession::STATUS_EXPIRED]);
        }

        return $session->refresh();
    }

    public function publicViewData(string $token): array
    {
        $session = $this->resolveSession($token);

        if (! $session) {
            return ['state' => 'invalid'];
        }

        $studyClass = StudyClass::query()
            ->with(['course:id,title', 'teacher:id,name', 'room:id,floor_id,room_number', 'room.floor:id,building_id,name', 'room.floor.building:id,name'])
            ->find($session->study_class_id);

        if (! $studyClass) {
            return ['state' => 'invalid'];
        }

        return [
            'state' => $this->sessionState($session),
            'session' => $this->presentSession($session, $studyClass),
            'classData' => [
                'id' => $studyClass->id,
                'title' => $studyClass->title,
                'course' => $studyClass->course?->title,
                'teacher' => $studyClass->teacher?->name ?? '-',
                'room' => $studyClass->room?->room_number ?? '-',
            ],
        ];
    }

    public function recordAttendance(Request $request, AttendanceSession $session, array $payload): StudentAttendance
    {
        $studyClass = StudyClass::query()->findOrFail($session->study_class_id);
        $student = Student::query()->find($payload['student_id']);

        if (! $student) {
            throw ValidationException::withMessages(['student_id' => 'Invalid Student ID or PIN.']);
        }

        $enrollment = StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('student_id', $student->id)
            ->where('enrollment_status', 'active')
            ->first();

        if (! $enrollment) {
            throw ValidationException::withMessages(['student_id' => 'You are not enrolled in this class.']);
        }

        if ($session->status !== AttendanceSession::STATUS_ACTIVE || ($session->expires_at && $session->expires_at->isPast())) {
            throw ValidationException::withMessages(['qr' => 'This attendance QR code has expired. Please scan the current QR code.']);
        }

        $date = Carbon::parse($session->attendance_date)->toDateString();
        $ipAddress = $request->ip();
        $location = $this->validateLocation($studyClass, $payload);
        $device = $this->presentDevice($payload['user_agent'] ?? null);
        $verification = $this->verificationState($location['verification_status']);

        try {
            return DB::transaction(function () use ($request, $session, $studyClass, $enrollment, $student, $date, $payload, $ipAddress, $location, $device, $verification): StudentAttendance {
                return StudentAttendance::create([
                    'study_class_id' => $studyClass->id,
                    'student_enrollment_id' => $enrollment->id,
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                    'tracked_by' => null,
                    'attendance_date' => $date,
                    'latitude' => $payload['latitude'],
                    'longitude' => $payload['longitude'],
                    'location_accuracy' => $payload['accuracy'],
                    'distance_from_class' => $location['distance'],
                    'ip_address' => $ipAddress,
                    'user_agent' => $payload['user_agent'] ?? null,
                    'browser' => $device['browser'],
                    'operating_system' => $device['operating_system'],
                    'device_type' => $device['device_type'],
                    'device_identifier' => $payload['device_identifier'],
                    'status' => 'present',
                    'verification_status' => $verification['status'],
                    'verification_reason' => $verification['reason'],
                    'source' => StudentAttendance::SOURCE_QR,
                    'note' => null,
                ]);
            });
        } catch (QueryException $exception) {
            if ((int) $exception->errorInfo[1] === 1062) {
                throw ValidationException::withMessages([
                    'student_id' => 'You have already submitted attendance for this class today.',
                ]);
            }

            throw $exception;
        }
    }

    public function teacherSummary(AttendanceSession $session, StudyClass $studyClass): array
    {
        $records = StudentAttendance::query()
            ->where('study_class_id', $studyClass->id)
            ->whereDate('attendance_date', $session->attendance_date->toDateString())
            ->orderBy('student_id')
            ->get();

        return [
            'present' => $records->count(fn (StudentAttendance $row) => $row->status === 'present'),
            'total' => $studyClass->enrollments()->where('enrollment_status', 'active')->count(),
            'records' => $records->map(fn (StudentAttendance $row): array => [
                'student_id' => $row->student_id,
                'status' => $row->status,
                'verification_status' => $row->verification_status ?? 'verified',
                'verification_reason' => $row->verification_reason,
                'time' => $row->created_at?->format('H:i') ?? '-',
            ])->values()->all(),
        ];
    }

    public function sessionUrl(AttendanceSession $session): string
    {
        return url('/attendance/qr/'.$session->qr_token);
    }

    public function presentSession(AttendanceSession $session, StudyClass $studyClass): array
    {
        return [
            'id' => $session->id,
            'class_id' => $studyClass->id,
            'attendance_date' => $session->attendance_date->format('Y-m-d'),
            'started_at' => $session->started_at?->format('Y-m-d H:i:s'),
            'expires_at' => $session->expires_at?->format('Y-m-d H:i:s'),
            'status' => $this->sessionState($session),
            'qr_url' => $this->sessionUrl($session),
        ];
    }

    private function ttlMinutes(StudyClass $studyClass): int
    {
        return 30;
    }

    private function sessionState(AttendanceSession $session): string
    {
        if ($session->status === AttendanceSession::STATUS_STOPPED) {
            return AttendanceSession::STATUS_STOPPED;
        }

        if ($session->expires_at !== null && $session->expires_at->isPast()) {
            return AttendanceSession::STATUS_EXPIRED;
        }

        return AttendanceSession::STATUS_ACTIVE;
    }

    private function validateLocation(StudyClass $studyClass, array $payload): array
    {
        $latitude = (float) $payload['latitude'];
        $longitude = (float) $payload['longitude'];
        $accuracy = (float) $payload['accuracy'];

        if ($studyClass->attendance_latitude === null || $studyClass->attendance_longitude === null || $studyClass->attendance_radius_meters === null) {
            return ['distance' => null, 'verification_status' => 'verified', 'reason' => null];
        }

        if ($accuracy > max((float) $studyClass->attendance_radius_meters, 50)) {
            throw ValidationException::withMessages([
                'location' => 'Your location could not be verified accurately. Please enable GPS and try again.',
            ]);
        }

        $distance = $this->distanceMeters(
            (float) $studyClass->attendance_latitude,
            (float) $studyClass->attendance_longitude,
            $latitude,
            $longitude,
        );

        if ($distance > (float) $studyClass->attendance_radius_meters) {
            throw ValidationException::withMessages([
                'location' => 'Attendance cannot be submitted because you are outside the allowed classroom location.',
            ]);
        }

        return ['distance' => round($distance, 2), 'verification_status' => 'verified', 'reason' => null];
    }

    private function verificationState(?string $locationStatus): array
    {
        if ($locationStatus !== null && $locationStatus !== 'verified') {
            return ['status' => 'suspicious', 'reason' => $locationStatus];
        }

        return ['status' => 'verified', 'reason' => null];
    }

    private function presentDevice(?string $userAgent): array
    {
        $userAgent = (string) $userAgent;
        $browser = 'Unknown';
        $operatingSystem = 'Unknown';
        $deviceType = str_contains(strtolower($userAgent), 'mobile') ? 'mobile' : 'desktop';

        if (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Chrome') && ! str_contains($userAgent, 'Edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari') && ! str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edg/')) {
            $browser = 'Edge';
        }

        if (str_contains($userAgent, 'Windows')) {
            $operatingSystem = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $operatingSystem = 'macOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $operatingSystem = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $operatingSystem = 'iOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $operatingSystem = 'Linux';
        }

        return [
            'browser' => $browser,
            'operating_system' => $operatingSystem,
            'device_type' => $deviceType,
        ];
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range, 2);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);

            if ($ipLong === false || $subnetLong === false) {
                return false;
            }

            $mask = -1 << (32 - (int) $bits);

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        return trim($ip) === trim($range);
    }
}
