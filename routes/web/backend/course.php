<?php

use App\Modules\Course\CategoryController;
use App\Modules\Course\SubCategoryController;
use App\Modules\Course\CourseTrackController;
use App\Modules\Course\CourseController;
use App\Modules\Course\CourseLessonController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('dashboard/course')->name('course.')->group(function () {
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Sub Categories
    Route::get('/subcategories', [SubCategoryController::class, 'index'])->name('subcategories');
    Route::get('/subcategories/create', [SubCategoryController::class, 'create'])->name('subcategories.create');
    Route::post('/subcategories', [SubCategoryController::class, 'store'])->name('subcategories.store');
    Route::get('/subcategories/{subCategory}/edit', [SubCategoryController::class, 'edit'])->name('subcategories.edit');
    Route::put('/subcategories/{subCategory}', [SubCategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('/subcategories/{subCategory}', [SubCategoryController::class, 'destroy'])->name('subcategories.destroy');

    // Tracks
    Route::get('/tracks', [CourseTrackController::class, 'index'])->name('tracks');  // ← Make sure this exists
    Route::get('/tracks/create', [CourseTrackController::class, 'create'])->name('tracks.create');
    Route::post('/tracks', [CourseTrackController::class, 'store'])->name('tracks.store');
    Route::get('/tracks/{track}', [CourseTrackController::class, 'show'])->name('tracks.show');
    Route::get('/tracks/{track}/edit', [CourseTrackController::class, 'edit'])->name('tracks.edit');
    Route::put('/tracks/{track}', [CourseTrackController::class, 'update'])->name('tracks.update');
    Route::delete('/tracks/{track}', [CourseTrackController::class, 'destroy'])->name('tracks.destroy');

    // Courses
    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    // Lessons
    Route::get('/lessons', [CourseLessonController::class, 'index'])->name('lessons');
    Route::get('/lessons/create', [CourseLessonController::class, 'create'])->name('lessons.create');
    Route::post('/lessons', [CourseLessonController::class, 'store'])->name('lessons.store');
    Route::get('/lessons/{lesson}', [CourseLessonController::class, 'show'])->name('lessons.show');
    Route::get('/lessons/{lesson}/edit', [CourseLessonController::class, 'edit'])->name('lessons.edit');
    Route::put('/lessons/{lesson}', [CourseLessonController::class, 'update'])->name('lessons.update');
    Route::delete('/lessons/{lesson}', [CourseLessonController::class, 'destroy'])->name('lessons.destroy');
});