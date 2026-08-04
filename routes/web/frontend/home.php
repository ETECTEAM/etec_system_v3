<?php

/*
|--------------------------------------------------------------------------
| Frontend Home Routes
|--------------------------------------------------------------------------
|
| The public site currently only has the Classes listing page, so the
| root URL just sends visitors straight there.
|
*/

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/classes');
