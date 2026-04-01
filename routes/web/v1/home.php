<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('home/Home');
});

Route::get('/{any}', function () {
    return Inertia::render('Home');
})->where('any', '.*');
