<?php

use App\Http\Controllers\Library\LibraryController;

Route::middleware(['auth:api', 'user.active'])->prefix('library')->group(function () {
    Route::get('/anime', [LibraryController::class, 'animeIndex']);
    Route::post('/anime', [LibraryController::class, 'animeStore']);
    Route::patch('/anime/{anime_id}', [LibraryController::class, 'animeUpdate']);
    Route::delete('/anime/{anime_id}', [LibraryController::class, 'animeDestroy']);

    Route::get('/manga', [LibraryController::class, 'mangaIndex']);
    Route::post('/manga', [LibraryController::class, 'mangaStore']);
    Route::patch('/manga/{manga_id}', [LibraryController::class, 'mangaUpdate']);
    Route::delete('/manga/{manga_id}', [LibraryController::class, 'mangaDestroy']);
});
