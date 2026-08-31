<?php

namespace App\Modules\Attendance\Services;

use App\Models\AttendanceSession;
use App\Models\StudentAttendance;
use App\Models\StudyClass;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use stdClass;
use Throwable;

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
                $this->cacheSession($session);

                return $session;
            }

            return $this->startSession($studyClass, $creator);
        }

        return $this->startSession($studyClass, $creator);
    }

    public function startSession(StudyClass $studyClass, User $creator): AttendanceSession
    {
        $now = Carbon::now('Asia/Phnom_Penh');
        $date = $now->toDateString();
        $token = Str::random(64);
        $expiresAt = $now->copy()->addMinutes($this->ttlMinutes($studyClass));

        $oldToken = null;
        $session = DB::transaction(function () use ($studyClass, $creator, $date, $now, $token, $expiresAt, &$oldToken): AttendanceSession {
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

            $oldToken = $session->qr_token;

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

        if ($oldToken !== null) {
            $this->forgetSessionCache($oldToken);
        }

        $this->cacheSession($session);

        return $session;
    }

    public function stopSession(AttendanceSession $session, User $user): AttendanceSession
    {
        $token = $session->qr_token;

        $session->update([
            'status' => AttendanceSession::STATUS_STOPPED,
            'stopped_by' => $user->id,
            'stopped_at' => now(),
        ]);

        $this->forgetSessionCache($token);

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
            $this->forgetSessionCache($token);
        }

        return $session->refresh();
    }

    public function resolveActiveSessionData(string $token): ?array
    {
        $cached = $this->cachedSessionData($token);

        if ($cached !== null) {
            if ($this->isActiveSessionData($cached)) {
                return $cached;
            }

            $this->expireSessionDataIfNeeded($cached);
            $this->forgetSessionCache($token);

            return null;
        }

        $row = DB::table('attendance_sessions')
            ->join('study_classes', 'study_classes.id', '=', 'attendance_sessions.study_class_id')
            ->where('attendance_sessions.qr_token', $token)
            ->select([
                'attendance_sessions.id',
                'attendance_sessions.study_class_id',
                'attendance_sessions.qr_token',
                'attendance_sessions.attendance_date',
                'attendance_sessions.started_at',
                'attendance_sessions.expires_at',
                'attendance_sessions.status',
                'study_classes.attendance_latitude',
                'study_classes.attendance_longitude',
                'study_classes.attendance_radius_meters',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $sessionData = $this->normalizeSessionData((array) $row);

        if (! $this->isActiveSessionData($sessionData)) {
            $this->expireSessionDataIfNeeded($sessionData);

            return null;
        }

        $this->putSessionCache($sessionData);

        return $sessionData;
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

    public function recordAttendanceFromQr(Request $request, array $sessionData, array $payload): StudentAttendance
    {
        $studentId = (int) $payload['student_id'];
        $enrollment = DB::table('student_enrollments')
            ->where('student_id', $studentId)
            ->where('study_class_id', (int) $sessionData['study_class_id'])
            ->where('enrollment_status', 'active')
            ->select(['id'])
            ->first();

        if (! $enrollment) {
            throw ValidationException::withMessages(['student_id' => 'You are not enrolled in this class.']);
        }

        $date = Carbon::parse($sessionData['attendance_date'])->toDateString();

        // An absence-blocked student can't self-check-in via QR.
        $lock = app(\App\Modules\AbsenceBlock\Services\AbsenceBlockEvaluator::class)
            ->evaluate($studentId, (int) $sessionData['study_class_id'], $date);

        if ($lock->locked) {
            throw ValidationException::withMessages([
                'student_id' => 'Your attendance is locked. Please see the school office.',
            ]);
        }

        $ipAddress = $request->ip();
        $location = $this->validateLocation($sessionData, $payload);
        $device = $this->presentDevice($payload['user_agent'] ?? null);
        $verification = $this->verificationState($location['verification_status']);

        try {
            return DB::transaction(function () use ($sessionData, $enrollment, $studentId, $date, $payload, $ipAddress, $location, $device, $verification): StudentAttendance {
                $this->assertSessionActiveForInsert((int) $sessionData['id'], (string) $sessionData['qr_token']);

                return StudentAttendance::create([
                    'study_class_id' => (int) $sessionData['study_class_id'],
                    'student_enrollment_id' => (int) $enrollment->id,
                    'attendance_session_id' => (int) $sessionData['id'],
                    'student_id' => $studentId,
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
            if ($this->isDuplicateAttendance($exception)) {
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

    private function validateLocation(array $sessionData, array $payload): array
    {
        $latitude = (float) $payload['latitude'];
        $longitude = (float) $payload['longitude'];
        $accuracy = (float) $payload['accuracy'];

        if ($sessionData['attendance_latitude'] === null || $sessionData['attendance_longitude'] === null || $sessionData['attendance_radius_meters'] === null) {
            return ['distance' => null, 'verification_status' => 'verified', 'reason' => null];
        }

        if ($accuracy > max((float) $sessionData['attendance_radius_meters'], 50)) {
            throw ValidationException::withMessages([
                'location' => 'Your location could not be verified accurately. Please enable GPS and try again.',
            ]);
        }

        $distance = $this->distanceMeters(
            (float) $sessionData['attendance_latitude'],
            (float) $sessionData['attendance_longitude'],
            $latitude,
            $longitude,
        );

        if ($distance > (float) $sessionData['attendance_radius_meters']) {
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

    private function cacheSession(AttendanceSession $session): void
    {
        $data = $this->sessionCachePayload($session);

        if ($data !== null) {
            $this->putSessionCache($data);
        }
    }

    private function sessionCachePayload(AttendanceSession $session): ?array
    {
        $studyClass = StudyClass::query()
            ->whereKey($session->study_class_id)
            ->first(['id', 'attendance_latitude', 'attendance_longitude', 'attendance_radius_meters']);

        if (! $studyClass) {
            return null;
        }

        return [
            'id' => (int) $session->id,
            'study_class_id' => (int) $session->study_class_id,
            'qr_token' => (string) $session->qr_token,
            'attendance_date' => $session->attendance_date->format('Y-m-d'),
            'started_at' => $session->started_at?->format('Y-m-d H:i:s'),
            'expires_at' => $session->expires_at?->format('Y-m-d H:i:s'),
            'status' => (string) $session->status,
            'attendance_latitude' => $studyClass->attendance_latitude,
            'attendance_longitude' => $studyClass->attendance_longitude,
            'attendance_radius_meters' => $studyClass->attendance_radius_meters,
        ];
    }

    private function cachedSessionData(string $token): ?array
    {
        try {
            $data = Cache::get($this->sessionCacheKey($token));
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $this->normalizeSessionData($data) : null;
    }

    private function putSessionCache(array $sessionData): void
    {
        try {
            Cache::put(
                $this->sessionCacheKey((string) $sessionData['qr_token']),
                $sessionData,
                now()->addSeconds($this->sessionCacheTtl($sessionData)),
            );
        } catch (Throwable) {
            // Cache is an optimization only; MySQL remains the source of truth.
        }
    }

    private function forgetSessionCache(string $token): void
    {
        try {
            Cache::forget($this->sessionCacheKey($token));
        } catch (Throwable) {
            // Cache is an optimization only; MySQL remains the source of truth.
        }
    }

    private function sessionCacheKey(string $token): string
    {
        return 'attendance:qr-session:'.$token;
    }

    private function sessionCacheTtl(array $sessionData): int
    {
        $expiresAt = Carbon::parse($sessionData['expires_at']);

        return (int) max(60, Carbon::now('Asia/Phnom_Penh')->diffInSeconds($expiresAt, false) + 300);
    }

    private function normalizeSessionData(array $data): array
    {
        return [
            'id' => (int) $data['id'],
            'study_class_id' => (int) $data['study_class_id'],
            'qr_token' => (string) $data['qr_token'],
            'attendance_date' => (string) $data['attendance_date'],
            'started_at' => $data['started_at'] === null ? null : (string) $data['started_at'],
            'expires_at' => $data['expires_at'] === null ? null : (string) $data['expires_at'],
            'status' => (string) $data['status'],
            'attendance_latitude' => $data['attendance_latitude'] ?? null,
            'attendance_longitude' => $data['attendance_longitude'] ?? null,
            'attendance_radius_meters' => $data['attendance_radius_meters'] ?? null,
        ];
    }

    private function isActiveSessionData(array $sessionData): bool
    {
        if ($sessionData['status'] !== AttendanceSession::STATUS_ACTIVE || $sessionData['expires_at'] === null) {
            return false;
        }

        return Carbon::parse($sessionData['expires_at'], 'Asia/Phnom_Penh')->isFuture();
    }

    private function expireSessionDataIfNeeded(array $sessionData): void
    {
        if ($sessionData['status'] !== AttendanceSession::STATUS_ACTIVE || $sessionData['expires_at'] === null) {
            return;
        }

        if (Carbon::parse($sessionData['expires_at'], 'Asia/Phnom_Penh')->isFuture()) {
            return;
        }

        DB::table('attendance_sessions')
            ->where('id', (int) $sessionData['id'])
            ->where('status', AttendanceSession::STATUS_ACTIVE)
            ->update(['status' => AttendanceSession::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    private function assertSessionActiveForInsert(int $sessionId, string $token): void
    {
        $active = DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->where('status', AttendanceSession::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now('Asia/Phnom_Penh'))
            ->lockForUpdate()
            ->first(['id']);

        if (! $active) {
            $this->forgetSessionCache($token);

            throw ValidationException::withMessages(['qr' => 'This attendance QR code has expired. Please scan the current QR code.']);
        }
    }

    private function isDuplicateAttendance(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return $sqlState === '23000' || $driverCode === '1062';
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
