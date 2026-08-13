<?php

namespace App\Modules\Website\Services;

use App\Models\StudyClass;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Marks "this browser already registered for class X" with one encrypted,
 * per-class cookie per StudyClass. Laravel's EncryptCookies middleware (on
 * the 'web' group by default) signs+encrypts every outgoing cookie and
 * rejects a tampered one on the way back in, so a value only ever reads back
 * non-null if this app actually issued it - no manual HMAC needed. Used both
 * to block re-submission server-side (RegisterClassRequest) and to flag a
 * class as already-registered on the public listing
 * (WebsiteContentService::presentClassPublic).
 */
class PublicRegistrationCookie
{
    private const TTL_MINUTES = 60 * 24 * 30;

    public function remember(StudyClass $studyClass, stdClass $enrollment): void
    {
        Cookie::queue(self::cookieName($studyClass->id), (string) $enrollment->id, self::TTL_MINUTES);
    }

    public function alreadyRegistered(Request $request, StudyClass $studyClass): bool
    {
        return in_array((int) $studyClass->id, $this->registeredClassIds($request, collect([$studyClass])), true);
    }

    /**
     * Verifies the cookie for every given class in a single query, rather than
     * one lookup per card on the public listing.
     *
     * @param  Collection<int, StudyClass>  $classes
     * @return array<int, int> study_class_id values confirmed registered
     */
    public function registeredClassIds(Request $request, Collection $classes): array
    {
        $enrollmentIdByClassId = $classes
            ->mapWithKeys(fn (StudyClass $studyClass) => [$studyClass->id => $request->cookie(self::cookieName($studyClass->id))])
            ->filter(fn (?string $value) => $value !== null && ctype_digit($value))
            ->map(fn (string $value) => (int) $value);

        if ($enrollmentIdByClassId->isEmpty()) {
            return [];
        }

        return DB::table('student_enrollments')
            ->where('source', 'public_website')
            ->where('enrollment_status', 'active')
            ->whereIn('study_class_id', $enrollmentIdByClassId->keys())
            ->get(['id', 'study_class_id'])
            ->filter(fn (stdClass $enrollment) => $enrollmentIdByClassId->get($enrollment->study_class_id) === (int) $enrollment->id)
            ->pluck('study_class_id')
            ->all();
    }

    private static function cookieName(int $studyClassId): string
    {
        return "registered_class_{$studyClassId}";
    }
}
