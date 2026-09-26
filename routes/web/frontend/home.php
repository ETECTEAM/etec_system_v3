<?php

/*
|--------------------------------------------------------------------------
| Frontend Home Routes
|--------------------------------------------------------------------------
|
| The root URL sends visitors to the dashboard; unauthenticated visitors get
| bounced to login from there by the 'auth' middleware as usual.
|
*/

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
