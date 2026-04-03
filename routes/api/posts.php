<?php

use App\Http\Controllers\Post\PostController;

Route::prefix('posts')->group(function () {
    Route::middleware('throttle:api')->group(function () {
        Route::get('/',               [PostController::class, 'index']);
        Route::get('/{id}',           [PostController::class, 'show']);
        Route::get('/{id}/comments',  [PostController::class, 'comments']);
    });

    Route::middleware(['auth:api', 'throttle:writes'])->group(function () {
        Route::post('/',       [PostController::class, 'store']);
        Route::patch('/{id}',  [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
    });
});
