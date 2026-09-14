<?php
// app/Modules/Course/SubCategoryController.php

namespace App\Modules\Course;

use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    public function index()
    {
        $subCategories = SubCategory::with('category')->get();
        $allCategories = Category::all();
        return Inertia::render('backend/courses/SubCategory/SubCategoryIndex', [
            'subCategories' => $subCategories,
            'allCategories' => $allCategories
        ]);
    }

    public function create()
    {
        // Change: use 'status' instead of 'is_active'
        $categories = Category::where('status', 'active')->get();
        return Inertia::render('backend/courses/SubCategory/SubCategoryForm', [
            'subCategory' => null,
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive'  // Change: use 'status'
        ]);

        SubCategory::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => $validated['status'] ?? 'active'  // Change: use 'status'
        ]);

        return redirect()->route('course.subcategories')->with('success', 'Sub Category created successfully');
    }

    public function edit(SubCategory $subCategory)
    {
        // Change: use 'status' instead of 'is_active'
        $categories = Category::where('status', 'active')->get();
        return Inertia::render('backend/courses/SubCategory/SubCategoryForm', [
            'subCategory' => $subCategory,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive'  // Change: use 'status'
        ]);

        $subCategory->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => $validated['status'] ?? 'active'  // Change: use 'status'
        ]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $subCategory->fresh('category')]);
        }

        return redirect()->route('course.subcategories')->with('success', 'Sub Category updated successfully');
    }

    public function destroy(SubCategory $subCategory)
    {
        $subCategory->delete();
        return redirect()->route('course.subcategories')->with('success', 'Sub Category deleted successfully');
    }
}