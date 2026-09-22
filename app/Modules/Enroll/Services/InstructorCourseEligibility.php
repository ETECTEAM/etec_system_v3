<?php

namespace App\Modules\Enroll\Services;

use App\Models\Course;
use App\Models\User;

class InstructorCourseEligibility
{
    /**
     * An instructor may be assigned only when one of their selected
     * specializations is the course track's sub-category.
     */
    public function canTeach(User $instructor, Course $course): bool
    {
        $skill = $course->track?->subCategory;

        if (! $skill) {
            return false;
        }

        $specializations = collect($instructor->instructorData?->specialization ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => mb_strtolower(trim((string) $value)));

        return $specializations->contains(mb_strtolower($skill->name))
            || $specializations->contains((string) $skill->id);
    }
}
