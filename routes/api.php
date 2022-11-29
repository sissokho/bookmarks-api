<?php

use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Authentication
    Route::post('/register', RegistrationController::class)->name('register');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
