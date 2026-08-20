<?php

namespace App\Modules\WorkSchedule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Time;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->search);
        $page = (int) $request->input('page', 1);
        $cacheKey = sprintf(
            'work_schedules:list:v%d:page%d:%s',
            WorkSchedule::cacheVersion(),
            $page,
            md5($search),
        );

        $schedules = Cache::remember($cacheKey, WorkSchedule::CACHE_TTL, function () use ($search) {
            $query = WorkSchedule::withCount('times');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            return $query->orderBy('id')->paginate(10)->withQueryString();
        });

        return Inertia::render('backend/work-schedules/Index', [
            'schedules' => $schedules,
            'filters' => ['search' => $request->search ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backend/work-schedules/Create', [
            'times' => Cache::remember(Time::CACHE_KEY, 3600, fn () => Time::orderBy('id', 'asc')->get()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:work_schedules,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'entries.*.time_id' => ['required', 'integer', 'exists:times,id'],
        ]);

        $schedule = WorkSchedule::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $entries = collect($validated['entries'])
            ->unique(fn (array $e) => "{$e['day_of_week']}_{$e['time_id']}")
            ->values();

        foreach ($entries as $entry) {
            $schedule->times()->create([
                'day_of_week' => $entry['day_of_week'],
                'time_id' => $entry['time_id'],
            ]);
        }

        return redirect()->route('work-schedules.index')
            ->with('success', 'Work schedule created successfully.');
    }

    public function edit(WorkSchedule $workSchedule): Response
    {
        $workSchedule->load('times.time');

        return Inertia::render('backend/work-schedules/Edit', [
            'schedule' => $workSchedule,
            'times' => Cache::remember(Time::CACHE_KEY, 3600, fn () => Time::orderBy('id', 'asc')->get()),
        ]);
    }

    public function update(Request $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('work_schedules', 'code')->ignore($workSchedule->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'entries.*.time_id' => ['required', 'integer', 'exists:times,id'],
        ]);

        $workSchedule->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $workSchedule->times()->delete();

        $entries = collect($validated['entries'])
            ->unique(fn (array $e) => "{$e['day_of_week']}_{$e['time_id']}")
            ->values();

        foreach ($entries as $entry) {
            $workSchedule->times()->create([
                'day_of_week' => $entry['day_of_week'],
                'time_id' => $entry['time_id'],
            ]);
        }

        return redirect()->route('work-schedules.index')
            ->with('success', 'Work schedule updated successfully.');
    }

    public function destroy(WorkSchedule $workSchedule): RedirectResponse
    {
        $workSchedule->delete();

        return redirect()->route('work-schedules.index')
            ->with('success', 'Work schedule deleted successfully.');
    }
}
