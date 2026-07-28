<?php

use App\Modules\Website\Controllers\MenuController;
use App\Modules\Website\Controllers\NewsController;
use App\Modules\Website\Controllers\PageController;
use App\Modules\Website\Controllers\SchoolSettingController;
use App\Modules\Website\Controllers\WebsiteVideoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super_admin|admin'])
    ->prefix('/dashboard/website')
    ->name('website.')
    ->group(function (): void {
        Route::get('/school-settings', [SchoolSettingController::class, 'edit'])->name('school-settings.edit');
        Route::put('/school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');
        Route::delete('/school-settings/logo', [SchoolSettingController::class, 'removeLogo'])->name('school-settings.logo.remove');

        Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
        Route::patch('/pages/{page}/status', [PageController::class, 'updateStatus'])->name('pages.status');
        Route::delete('/pages/{page}/hero-image', [PageController::class, 'removeHeroImage'])->name('pages.hero-image.remove');
        Route::resource('pages', PageController::class);

        Route::put('/menus/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');
        Route::patch('/menus/{menu}/status', [MenuController::class, 'updateStatus'])->name('menus.status');
        Route::resource('menus', MenuController::class)->except(['show']);

        Route::patch('/videos/{video}/status', [WebsiteVideoController::class, 'updateStatus'])->name('videos.status');
        Route::resource('videos', WebsiteVideoController::class)->except(['show']);

        Route::patch('/news/{news}/status', [NewsController::class, 'updateStatus'])->name('news.status');
        Route::resource('news', NewsController::class)->except(['show']);
    });
