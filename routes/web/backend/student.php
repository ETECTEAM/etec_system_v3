<?php

use App\Models\Time;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// middleware('auth')
Route::prefix('/dashboard/students')->group(function () {
    Route::get('/', function () {
        return Inertia::render('backend/students/ClassList');
    })->name('students.index');
    Route::get('/create', function () {
        return Inertia::render('backend/students/CreateClass');
    })->name('students.create');
});
