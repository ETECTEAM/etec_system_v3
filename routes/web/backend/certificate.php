<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'role:super_admin|admin|instructor'])->get('/dashboard/certificates', function () {
    return Inertia::render('backend/certificates/Index');
})->name('dashboard.certificates.index');
