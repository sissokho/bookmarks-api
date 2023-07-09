<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(base_path('routes/api/v1/routes.php'));

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
