<?php

use App\Http\Controllers\Forum\ForumController;

Route::prefix('forum')->group(function () {
    Route::middleware('throttle:api')->group(function () {
        Route::get('/topics',      [ForumController::class, 'index']);
        Route::get('/topics/{id}', [ForumController::class, 'show']);
    });

    Route::middleware(['auth:api', 'throttle:writes'])->group(function () {
        Route::post('/topics',            [ForumController::class, 'store']);
        Route::delete('/topics/{id}',     [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
    });
});
