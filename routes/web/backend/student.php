<?php

use App\Models\Time;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
<<<<<<< HEAD

Route::middleware('auth')->prefix('/dashboard/students')->group(function(){
=======
// middleware('auth')
Route::prefix('/dashboard/students')->group(function () {
>>>>>>> 50ba0a8 (Update feature register class chang to enroll student)
    Route::get('/', function () {
        return Inertia::render('backend/students/ClassList');
    })->name('students.index');
    Route::get('/create', function () {
        return Inertia::render('backend/students/CreateClass');
    })->name('students.create');
});
