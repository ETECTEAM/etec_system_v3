<?php
// app/Modules/Course/CategoryController.php

namespace App\Modules\Course;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        // Change: use 'status' instead of 'is_active'
        $categories = Category::with('subCategories')->get();
        return Inertia::render('backend/courses/Categories', [
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return Inertia::render('backend/courses/CategoryForm', [
            'category' => null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive'  // Change: use 'status'
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => $validated['status'] ?? 'active'  // Change: use 'status'
        ]);

        return redirect()->route('course.categories')->with('success', 'Category created successfully');
    }

    public function edit(Category $category)
    {
        return Inertia::render('backend/courses/CategoryForm', [
            'category' => $category
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive'  // Change: use 'status'
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => $validated['status'] ?? 'active'  // Change: use 'status'
        ]);

        return redirect()->route('course.categories')->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('course.categories')->with('success', 'Category deleted successfully');
    }
}