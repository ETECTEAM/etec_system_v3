<?php

use App\Models\Time;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// middleware('auth')
Route::prefix('/dashboard/students')->group(function(){
    Route::get('/', function () {
        return Inertia::render('backend/students/List');
    })->name('students.index');
    Route::get('/form' , function(){
        $times = Time::orderBy('time_name')->get();
        return Inertia::render('backend/students/Form', ['times' => $times]);
    })->name('students.form');
});