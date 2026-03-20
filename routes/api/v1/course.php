<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('courses')->middleware(['auth:sanctum'])->group(function (): void {
    
        Route::get('/', function (): JsonResponse {
            return response()->json([
                'message' => 'Course list endpoint.',
            ]);
        })->middleware('permission:course.view');

});
