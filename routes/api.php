<?php

use App\Http\Controllers\Api\V1\Archives\DestroyController as ArchiveDestroyController;
use App\Http\Controllers\Api\V1\Archives\UpdateController as ArchiveUpdateController;
use App\Http\Controllers\Api\V1\Auth\ApiKeyRegenerationController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Bookmarks\DestroyController as BookmarkDestroyController;
use App\Http\Controllers\Api\V1\Bookmarks\ShowController as BookmarkShowController;
use App\Http\Controllers\Api\V1\Bookmarks\StoreController as BookmarkStoreController;
use App\Http\Controllers\Api\V1\Bookmarks\UpdateController as BookmarkUpdateController;
use App\Http\Controllers\Api\V1\Favorites\DestroyController as FavoriteDestroyController;
use App\Http\Controllers\Api\V1\Favorites\UpdateController as FavoriteUpdateController;
use App\Http\Controllers\Api\V1\Tags\IndexController as TagIndexController;
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

    // Bookmarks
    Route::middleware('auth:sanctum')->name('bookmarks.')->group(function () {
        Route::post('/bookmarks', BookmarkStoreController::class)->name('store');
        Route::get('/bookmarks/{bookmark}', BookmarkShowController::class)->name('show');
        Route::patch('/bookmarks/{bookmark}', BookmarkUpdateController::class)->name('update');
        Route::delete('/bookmarks/{bookmark}', BookmarkDestroyController::class)->name('destroy');
    });

    // Favorites
    Route::middleware('auth:sanctum')->name('favorites.')->group(function () {
        Route::patch('/favorites/{bookmark}', FavoriteUpdateController::class)->name('update');
        Route::delete('/favorites/{bookmark}', FavoriteDestroyController::class)->name('destroy');
    });

    // Archives
    Route::middleware('auth:sanctum')->name('archives.')->group(function () {
        Route::patch('/archives/{bookmark}', ArchiveUpdateController::class)->name('update')->withTrashed();
        Route::delete('/archives/{bookmark}', ArchiveDestroyController::class)->name('destroy')->withTrashed();
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
