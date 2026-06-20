<?php

namespace App\Modules\Class\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassCategory;
use App\Models\ClassType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassCategory::with('classType');

        if ($request->filled('class_type_id')) {
            $query->where('class_type_id', $request->class_type_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('category_name')->paginate(10)->withQueryString();
        $classTypes = ClassType::where('is_active', true)->orderBy('type_name')->get();

        return Inertia::render('backend/classes/class-category/index', [
            'categories' => $categories,
            'classTypes' => $classTypes,
        ]);
    }

    public function create()
    {
        $classTypes = ClassType::where('is_active', true)->orderBy('type_name')->get();

        return Inertia::render('backend/classes/class-category/create', [
            'classTypes' => $classTypes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_type_id' => 'required|integer|exists:class_type,class_type_id',
            'category_name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('class_category')->where(fn($q) =>
                    $q->where('class_type_id', $request->class_type_id)
                ),
            ],
            'category_code' => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:255',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ClassCategory::create($validated);

        return redirect()->route('class-categories.index')
            ->with('success', 'Class category created successfully.');
    }

    public function show(int $id)
    {
        $category = ClassCategory::with('classType')->findOrFail($id);

        return Inertia::render('backend/classes/class-category/show', [
            'category' => $category
        ]);
    }

    public function edit(int $id)
    {
        $category   = ClassCategory::findOrFail($id);
        $classTypes = ClassType::where('is_active', true)->orderBy('type_name')->get();

        return Inertia::render('backend/classes/class-category/edit', [
            'category'   => $category,
            'classTypes' => $classTypes
        ]);
    }

    public function update(Request $request, int $id)
    {
        $category = ClassCategory::findOrFail($id);

        $validated = $request->validate([
            'class_type_id' => 'required|integer|exists:class_type,class_type_id',
            'category_name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('class_category')
                    ->where(fn($q) => $q->where('class_type_id', $request->class_type_id))
                    ->ignore($id, 'class_category_id'),
            ],
            'category_code' => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:255',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('class-categories.index')
            ->with('success', 'Class category updated successfully.');
    }

    public function destroy(int $id)
    {
        $category = ClassCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('class-categories.index')
            ->with('success', 'Class category deleted successfully.');
    }
}