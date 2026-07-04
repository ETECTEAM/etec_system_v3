<?php

namespace App\Modules\ShiftTemplate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShiftTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ShiftTemplate::withCount('blocks');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return Inertia::render('backend/shift-templates/Index', [
            'templates' => $query->orderBy('id')->paginate(10)->withQueryString(),
            'filters' => ['search' => $request->search ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backend/shift-templates/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:shift_templates,code'],
            'employment_type' => ['nullable', 'string', Rule::in(['full_time', 'part_time'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'blocks.*.period' => ['nullable', 'string', 'max:50'],
            'blocks.*.start_time' => ['required', 'date_format:H:i'],
            'blocks.*.end_time' => ['required', 'date_format:H:i', 'after:blocks.*.start_time'],
        ]);

        $template = ShiftTemplate::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'employment_type' => $validated['employment_type'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['blocks'] as $block) {
            $template->blocks()->create([
                'day_of_week' => $block['day_of_week'],
                'period' => $block['period'] ?? null,
                'start_time' => $block['start_time'],
                'end_time' => $block['end_time'],
            ]);
        }

        return redirect()->route('shift-templates.index')
            ->with('success', 'Shift template created successfully.');
    }

    public function edit(ShiftTemplate $shiftTemplate): Response
    {
        $shiftTemplate->load('blocks');

        return Inertia::render('backend/shift-templates/Edit', [
            'template' => $shiftTemplate,
        ]);
    }

    public function update(Request $request, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('shift_templates', 'code')->ignore($shiftTemplate->id)],
            'employment_type' => ['nullable', 'string', Rule::in(['full_time', 'part_time'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'blocks' => ['required', 'array', 'min:1'],
            'blocks.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'blocks.*.period' => ['nullable', 'string', 'max:50'],
            'blocks.*.start_time' => ['required', 'date_format:H:i'],
            'blocks.*.end_time' => ['required', 'date_format:H:i', 'after:blocks.*.start_time'],
        ]);

        $shiftTemplate->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'employment_type' => $validated['employment_type'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $shiftTemplate->blocks()->delete();

        foreach ($validated['blocks'] as $block) {
            $shiftTemplate->blocks()->create([
                'day_of_week' => $block['day_of_week'],
                'period' => $block['period'] ?? null,
                'start_time' => $block['start_time'],
                'end_time' => $block['end_time'],
            ]);
        }

        return redirect()->route('shift-templates.index')
            ->with('success', 'Shift template updated successfully.');
    }

    public function destroy(ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $shiftTemplate->delete();

        return redirect()->route('shift-templates.index')
            ->with('success', 'Shift template deleted successfully.');
    }
}
