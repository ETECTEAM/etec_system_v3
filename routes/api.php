<?php

use Illuminate\Support\Facades\Route;


Route::prefix("")->group(function () {
    includeRouteFiles(__DIR__ ."/api/v1");
});