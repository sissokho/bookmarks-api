<?php

use App\Http\Controllers\Api\V1\Archives;
use App\Http\Controllers\Api\V1\Auth\ApiKeyRegenerationController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Bookmarks;
use App\Http\Controllers\Api\V1\Favorites;
use App\Http\Controllers\Api\V1\Tags;
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
        Route::get('/tags', Tags\IndexController::class)->name('index');
    });

    // Bookmarks
    Route::middleware('auth:sanctum')->name('bookmarks.')->group(function () {
        Route::get('/bookmarks', Bookmarks\IndexController::class)->name('index');
        Route::post('/bookmarks', Bookmarks\StoreController::class)->name('store');
        Route::get('/bookmarks/{bookmark}', Bookmarks\ShowController::class)->name('show')->withTrashed();
        Route::patch('/bookmarks/{bookmark}', Bookmarks\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/bookmarks/{bookmark}', Bookmarks\DestroyController::class)->name('destroy')->withTrashed();
    });

    // Favorites
    Route::middleware('auth:sanctum')->name('favorites.')->group(function () {
        Route::get('/favorites', Favorites\IndexController::class)->name('index');
        Route::patch('/favorites/{bookmark}', Favorites\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/favorites/{bookmark}', Favorites\DestroyController::class)->name('destroy')->withTrashed();
    });

    // Archives
    Route::middleware('auth:sanctum')->name('archives.')->group(function () {
        Route::get('/archives', Archives\IndexController::class)->name('index');
        Route::patch('/archives/{bookmark}', Archives\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/archives/{bookmark}', Archives\DestroyController::class)->name('destroy')->withTrashed();
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
