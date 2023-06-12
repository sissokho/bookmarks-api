<?php

use App\Http\Controllers\Api\V1\Auth\ApiKeyRegenerationController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Bookmarks\StoreController as BookmarkStoreController;
use App\Http\Controllers\Api\V1\Tags\IndexController as TagIndexController;
use App\Http\Controllers\Api\V1\Tags\ShowController as TagShowController;
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
    Route::post('/regenerate-api-key', ApiKeyRegenerationController::class)->name('regenerate');

    // Tags
    Route::middleware('auth:sanctum')->name('tags.')->group(function () {
        Route::get('/tags', TagIndexController::class)->name('index');
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
