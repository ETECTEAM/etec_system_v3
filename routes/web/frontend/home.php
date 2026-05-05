<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
        return redirect('/dashboard');
    }

    return Inertia::render('home/Home');
});

Route::get('/{any}', function () {
    return Inertia::render('home/Home');
})->where('any', '.*');
