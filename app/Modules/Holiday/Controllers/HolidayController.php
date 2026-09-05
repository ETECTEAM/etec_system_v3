<?php

namespace App\Modules\Holiday\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Modules\Holiday\Requests\SaveHolidayRequest;
use App\Modules\Holiday\Services\HolidayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayService $holidays) {}

    public function index(Request $request): Response
    {
        $month = $this->resolveMonth($request->query('month'));

        $calendarStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $visibleHolidays = Holiday::query()
            ->whereBetween('date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('date')
            ->get();

        return Inertia::render('backend/holidays/Index', [
            'month' => $month->format('Y-m'),
            'today' => Carbon::now('Asia/Phnom_Penh')->toDateString(),
            'yearRange' => $this->yearRange($month),
            'holidays' => $this->presentGroups($this->fullGroupsFor($visibleHolidays)),
            'holidayDates' => $visibleHolidays
                ->map(fn (Holiday $holiday): array => [
                    'date' => $holiday->date->toDateString(),
                    'name' => $holiday->name,
                    'group_id' => $holiday->group_id ?: (string) $holiday->id,
                ])
                ->values(),
        ]);
    }

    private function fullGroupsFor($visibleHolidays)
    {
        if ($visibleHolidays->isEmpty()) {
            return collect();
        }

        $groupIds = $visibleHolidays->pluck('group_id')->filter()->unique()->values();
        $legacyIds = $visibleHolidays->filter(fn (Holiday $holiday): bool => blank($holiday->group_id))->pluck('id')->values();

        return Holiday::query()
            ->where(function ($query) use ($groupIds, $legacyIds): void {
                if ($groupIds->isNotEmpty()) {
                    $query->whereIn('group_id', $groupIds);
                }

                if ($legacyIds->isNotEmpty()) {
                    $method = $groupIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('id', $legacyIds);
                }
            })
            ->orderBy('date')
            ->get();
    }

    private function resolveMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', $month, 'Asia/Phnom_Penh')->startOfMonth();
            } catch (\Throwable) {}
        }

        return Carbon::now('Asia/Phnom_Penh')->startOfMonth();
    }

    private function yearRange(Carbon $month): array
    {
        $todayYear = (int) Carbon::now('Asia/Phnom_Penh')->format('Y');
        $activeYear = (int) $month->format('Y');
        $oldestHoliday = Holiday::query()->min('date');
        $newestHoliday = Holiday::query()->max('date');

        $oldestYear = $oldestHoliday ? (int) Carbon::parse($oldestHoliday, 'Asia/Phnom_Penh')->format('Y') : $todayYear;
        $newestYear = $newestHoliday ? (int) Carbon::parse($newestHoliday, 'Asia/Phnom_Penh')->format('Y') : $todayYear;

        return [
            'start' => min($oldestYear, $activeYear, $todayYear) - 1,
            'end' => max($newestYear, $activeYear, $todayYear) + 1,
        ];
    }

    public function store(SaveHolidayRequest $request): RedirectResponse
    {
        $this->holidays->saveRange($request->validated());

        return back()->with('success', 'Holiday saved.');
    }

    public function update(string $groupId, SaveHolidayRequest $request): RedirectResponse
    {
        $this->holidays->saveRange($request->validated(), $groupId);

        return back()->with('success', 'Holiday updated.');
    }

    public function destroy(string $groupId): RedirectResponse
    {
        $this->holidays->deleteRange($groupId);

        return back()->with('success', 'Holiday deleted.');
    }

    private function presentGroups($holidays): array
    {
        return $holidays
            ->groupBy(fn (Holiday $holiday): string => $holiday->group_id ?: (string) $holiday->id)
            ->map(function ($rows, string $groupId): array {
                $first = $rows->first();

                return [
                    'group_id' => $groupId,
                    'name' => $first->name,
                    'start_date' => ($first->start_date ?: $rows->min('date'))->toDateString(),
                    'end_date' => ($first->end_date ?: $rows->max('date'))->toDateString(),
                    'description' => $first->description,
                    'dates' => $rows->map(fn (Holiday $holiday): string => $holiday->date->toDateString())->values(),
                ];
            })
            ->sortBy('start_date')
            ->values()
            ->all();
    }
}
