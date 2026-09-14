<?php

namespace App\Modules\Class\Controllers; 

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ClassTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('backend/classes/class-type/index');
    }

    public function paginatedIndex(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page') === 'all'
            ? max(1, ClassType::query()->count())
            : max(1, min(1000, $request->integer('per_page', 10)));

        $classTypes = ClassType::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('type_name', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->orderBy('type_name')
            ->paginate($perPage);

        return response()->json($classTypes);
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
        // Simply fetch the class type
        $classType = ClassType::findOrFail($id);

        return Inertia::render('backend/classes/class-type/show', [
            'classType' => $classType
        ]);
    }

    public function edit(int $id)
    {
        $classType = ClassType::findOrFail($id);

        return Inertia::render('backend/classes/class-type/edit', [
            'classType' => $classType
        ]);
    }

    public function update(Request $request, int $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $classType = ClassType::findOrFail($id);

        $validated = $request->validate([
            'type_name'   => 'required|string|max:100|unique:class_type,type_name,' . $id . ',class_type_id',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $classType->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Class type updated successfully.',
                'data' => $classType->fresh(),
            ]);
        }

        return redirect()->route('class-types.index')
            ->with('success', 'Class type updated successfully.');
    }

    public function destroy(int $id)
    {
        $classType = ClassType::findOrFail($id);

        // If you deleted the categories table, delete this IF statement
        $classType->delete();
        
        return redirect()->route('class-types.index')
            ->with('success', 'Class type deleted successfully.');
    }
}
