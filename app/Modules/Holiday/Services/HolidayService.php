<?php

namespace App\Modules\Holiday\Services;

use App\Models\ClassSession;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HolidayService
{
    /**
     * @param  array{name: string, dates?: array<int, string>|null, start_date?: string|null, end_date?: string|null, description?: string|null}  $data
     */
    public function saveRange(array $data, ?string $groupId = null): string
    {
        $dates = collect($data['dates'] ?? [])
            ->filter()
            ->map(fn (string $date): string => Holiday::normalizeDate($date))
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            $startDate = $data['start_date'] ?? null;
            $start = Holiday::normalizeDate($startDate);
            $end = Holiday::normalizeDate($data['end_date'] ?? $startDate);
            $dates = Holiday::datesBetween($start, $end);
        }

        $start = $dates->min();
        $end = $dates->max();
        $groupId ??= (string) Str::uuid();

        $conflictingDate = Holiday::query()
            ->whereIn('date', $dates)
            ->when($groupId, fn ($query) => $query->where('group_id', '!=', $groupId))
            ->orderBy('date')
            ->value('date');

        if ($conflictingDate) {
            throw ValidationException::withMessages([
                'dates' => "A holiday already exists on {$conflictingDate}.",
            ]);
        }

        DB::transaction(function () use ($data, $dates, $groupId, $start, $end): void {
            Holiday::query()->where('group_id', $groupId)->delete();

            Holiday::query()->insert($dates->map(fn (string $date): array => [
                'group_id' => $groupId,
                'date' => $date,
                'name' => $data['name'],
                'start_date' => $start,
                'end_date' => $end,
                'description' => $data['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());

            $this->skipUnresolvedSessions($dates->all());
        });

        return $groupId;
    }

    public function deleteRange(string $groupId): void
    {
        Holiday::query()->where('group_id', $groupId)->delete();
    }

    public function skipUnresolvedSession(ClassSession $session): void
    {
        if (! in_array($session->status, [
            ClassSession::STATUS_PENDING,
            ClassSession::STATUS_PRE_ATTENDANCE,
            ClassSession::STATUS_PARTIAL,
        ], true)) {
            return;
        }

        $session->update([
            'status' => ClassSession::STATUS_SKIPPED,
            'recorded_at' => null,
            'grace_minutes_used' => null,
        ]);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function skipUnresolvedSessions(array $dates): void
    {
        ClassSession::query()
            ->whereIn('status', [
                ClassSession::STATUS_PENDING,
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ])
            ->whereIn(DB::raw('DATE(session_date)'), $dates)
            ->get()
            ->each(fn (ClassSession $session) => $this->skipUnresolvedSession($session));
    }
}
