<?php

namespace App\Modules\Class\Controllers; 

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassTypeController extends Controller
{
    public function index()
    {
        $classTypes = ClassType::withCount('classCategories')
            ->orderBy('type_name')
            ->paginate(10);

        return Inertia::render('backend/classes/class-type/index', [
            'classTypes' => $classTypes,
        ]);
    }

    public function create()
    {
        return Inertia::render('backend/classes/class-type/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_name'   => 'required|string|max:100|unique:class_type,type_name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        
        ClassType::create($validated);

        return redirect()->route('class-types.index')
            ->with('success', 'Class type created successfully.');
    }

    public function show(int $id)
    {
        $classType = ClassType::with('classCategories')->findOrFail($id);

        return Inertia::render('ClassType/Show', [
            'classType' => $classType->toArray()  // force full serialization including relations
        ]);
    }

    public function edit(int $id)
    {
        $classType = ClassType::findOrFail($id);

        return Inertia::render('backend/classes/class-type/edit', [
            'classType' => $classType
        ]);
    }

    public function update(Request $request, int $id)
    {
        $classType = ClassType::findOrFail($id);

        $validated = $request->validate([
            'type_name'   => 'required|string|max:100|unique:class_type,type_name,' . $id . ',class_type_id',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $classType->update($validated);

        return redirect()->route('class-types.index')
            ->with('success', 'Class type updated successfully.');
    }

    public function destroy(int $id)
    {
        $classType = ClassType::findOrFail($id);

        if ($classType->classCategories()->exists()) {
            return redirect()->route('class-types.index')
                ->with('error', 'Cannot delete class type with existing categories.');
        }

        $classType->delete();
        return redirect()->route('class-types.index')
            ->with('success', 'Class type deleted successfully.');
    }
}