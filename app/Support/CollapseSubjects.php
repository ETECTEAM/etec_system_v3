<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Support\Str;

/**
 * "Basic IT" (course track "Code & Network") is taught as two halves - Code and
 * Network - that two instructors split when a class is collapsed. Which half
 * suits an instructor follows the sub-categories they picked as their
 * specialization: a network one takes Network, a programming / development one
 * takes Code. The Collapse Class dialog uses this to suggest the "Teaches" text
 * for both instructors instead of making anyone type it.
 */
final class CollapseSubjects
{
    public const CODE = 'Code';

    public const NETWORK = 'Network';

    private const TRACK = 'Code & Network';

    /** Words that make a specialization point at each half (matched case-insensitively). */
    private const KEYWORDS = [
        self::CODE => ['code', 'coding', 'programming', 'development', 'web', 'mobile', 'desktop', 'software'],
        self::NETWORK => ['network'],
    ];

    /**
     * The pair a course is split into, or [] when it isn't a two-part course.
     *
     * @return array<int, string>
     */
    public static function forCourse(?Course $course): array
    {
        return Str::lower(trim((string) $course?->track?->name)) === Str::lower(self::TRACK)
            ? [self::CODE, self::NETWORK]
            : [];
    }

    /**
     * The half an instructor's specialization points at, or null when it doesn't
     * point at exactly one (nothing relevant, or both Network and Code).
     *
     * @param  array<int, mixed>|null  $specializations  sub-category names from the instructor's profile
     */
    public static function forSpecialization(?array $specializations): ?string
    {
        $text = collect($specializations ?? [])->map(fn ($value) => Str::lower((string) $value));

        $halves = collect(self::KEYWORDS)
            ->filter(fn (array $words) => $text->contains(fn (string $value) => Str::contains($value, $words)))
            ->keys();

        return $halves->count() === 1 ? $halves->first() : null;
    }
}
