<?php

use App\Modules\Website\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('pages.show');
