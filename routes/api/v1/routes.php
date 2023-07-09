<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Archives;
use App\Http\Controllers\Api\V1\Auth\ApiKeyRegenerationController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Http\Controllers\Api\V1\Bookmarks;
use App\Http\Controllers\Api\V1\Favorites;
use App\Http\Controllers\Api\V1\Tags;

Route::post('/register', RegistrationController::class)->name('register');
Route::post('/regenerate-api-key', ApiKeyRegenerationController::class)->name('regenerate');

Route::middleware('auth:sanctum')->group(function () {
    // Tags
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', Tags\IndexController::class)->name('index');
        Route::get('/{tag:slug}/bookmarks', Tags\TaggedBookmarkController::class)->name('show-bookmarks');
    });

    // Bookmarks
    Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
        Route::get('/', Bookmarks\IndexController::class)->name('index');
        Route::post('/', Bookmarks\StoreController::class)->name('store');
        Route::get('/{bookmark}', Bookmarks\ShowController::class)->name('show')->withTrashed();
        Route::patch('/{bookmark}', Bookmarks\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/{bookmark}', Bookmarks\DestroyController::class)->name('destroy')->withTrashed();
    });

    // Favorites
    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/', Favorites\IndexController::class)->name('index');
        Route::patch('/{bookmark}', Favorites\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/{bookmark}', Favorites\DestroyController::class)->name('destroy')->withTrashed();
    });

    // Archives
    Route::prefix('archives')->name('archives.')->group(function () {
        Route::get('/', Archives\IndexController::class)->name('index');
        Route::patch('/{bookmark}', Archives\UpdateController::class)->name('update')->withTrashed();
        Route::delete('/{bookmark}', Archives\DestroyController::class)->name('destroy')->withTrashed();
    });
});
