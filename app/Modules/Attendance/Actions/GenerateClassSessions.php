<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\Holiday;
use App\Models\StudyClass;
use App\Models\Term;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Creates today's ClassSession rows — the concrete "this class meets on this calendar
 * date" fact that study_classes never stores on its own (it only has a term's weekday
 * pattern + a time slot). Idempotent: safe to run more than once for the same date.
 */
class GenerateClassSessions
{
    /**
     * @return array{created: int, skipped_no_students: int, skipped_no_match: int}
     */
    public function handle(Carbon $date): array
    {
        $result = ['created' => 0, 'skipped_no_students' => 0, 'skipped_no_match' => 0];

        if (Holiday::isHoliday($date)) {
            return $result;
        }

        $dayName = $date->format('l');
        $terms = Term::query()->get()->keyBy('id');

        $activeStudentCounts = StudyClass::query()
            ->withCount(['enrollments as active_students' => fn ($query) => $query->where('enrollment_status', 'active')])
            ->whereIn('status', ['upcoming', 'active', 'pre_end'])
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $date))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->with(['term', 'time', 'instructors'])
            ->get();

        foreach ($activeStudentCounts as $class) {
            try {
                $slot = $this->slotForDate($class, $dayName, $terms);
            } catch (\Throwable $e) {
                Log::warning('GenerateClassSessions: could not resolve slot for class.', [
                    'study_class_id' => $class->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($slot === null) {
                $result['skipped_no_match']++;

                continue;
            }

            $timeRange = StudyClass::parseTimeRange($class->time?->time_name);

            if (! $timeRange['start'] || ! $timeRange['end']) {
                $result['skipped_no_match']++;

                continue;
            }

            $scheduledStart = $date->copy()->setTimeFromTimeString($timeRange['start']);
            $scheduledEnd = $date->copy()->setTimeFromTimeString($timeRange['end']);

            $hasStudents = (int) $class->active_students > 0;

            if (! $hasStudents) {
                $result['skipped_no_students']++;
            }

            ClassSession::updateOrCreate(
                ['study_class_id' => $class->id, 'session_date' => $date->toDateString()],
                [
                    'instructor_id' => $slot['instructor_id'],
                    'scheduled_start' => $scheduledStart,
                    'scheduled_end' => $scheduledEnd,
                    'status' => $hasStudents ? ClassSession::STATUS_PENDING : ClassSession::STATUS_SKIPPED,
                ],
            );

            $result['created']++;
        }

        return $result;
    }

    /**
     * Which instructor teaches this class on this weekday, and under which days. A class
     * shared between two instructors ("Collapse Class" — study_class_instructors) has each
     * teaching different days, so the class's own term isn't necessarily the right one —
     * whichever instructor's own slot covers $dayName is. An unshared class just uses its
     * own teacher_id and term.
     */
    private function slotForDate(StudyClass $class, string $dayName, $terms): ?array
    {
        if ($class->instructors->isNotEmpty()) {
            foreach ($class->instructors as $instructor) {
                $termId = $instructor->pivot->term_id;
                $termName = $termId ? $terms->get($termId)?->term_name : $class->term?->term_name;

                if (in_array($dayName, StudyClass::parseTermDays($termName), true)) {
                    return ['instructor_id' => $instructor->id];
                }
            }

            return null;
        }

        if (in_array($dayName, StudyClass::parseTermDays($class->term?->term_name), true)) {
            return ['instructor_id' => $class->teacher_id];
        }

        return null;
    }
}
