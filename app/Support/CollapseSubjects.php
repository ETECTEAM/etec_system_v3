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
 * for both instructors instead of making anyone type it, and to offer only the
 * instructors who can teach the other half as the second instructor.
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
     * Every half an instructor's specialization covers: Code, Network, both, or none.
     *
     * @param  array<int, mixed>|null  $specializations  sub-category names, as the profile page saves them.
     *                                                    Rows written by the dev seeders hold the sub-category
     *                                                    id instead; those are resolved through $subCategoryNames.
     * @param  array<int|string, string>  $subCategoryNames  sub-category id => name
     * @return array<int, string>
     */
    public static function halvesFor(?array $specializations, array $subCategoryNames = []): array
    {
        $text = collect($specializations ?? [])
            ->map(fn ($value) => is_numeric($value) && isset($subCategoryNames[(int) $value])
                ? $subCategoryNames[(int) $value]
                : $value)
            ->map(fn ($value) => Str::lower((string) $value));

        return collect(self::KEYWORDS)
            ->filter(fn (array $words) => $text->contains(fn (string $value) => Str::contains($value, $words)))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * The half an instructor's specialization points at, or null when it doesn't
     * point at exactly one (nothing relevant, or both Network and Code).
     *
     * @param  array<int, mixed>|null  $specializations
     * @param  array<int|string, string>  $subCategoryNames  sub-category id => name
     */
    public static function forSpecialization(?array $specializations, array $subCategoryNames = []): ?string
    {
        $halves = self::halvesFor($specializations, $subCategoryNames);

        return count($halves) === 1 ? $halves[0] : null;
    }
}
