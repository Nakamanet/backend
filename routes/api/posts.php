<?php

use App\Http\Controllers\Post\PostController;

Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        // Personal collections — must come BEFORE /{id} to avoid route collision
        Route::get('/me/saved',              [PostController::class, 'savedPosts']);
        Route::get('/me/archived',           [PostController::class, 'archivedOwnPosts']);
        Route::get('/me/archived-from-feed', [PostController::class, 'archivedFromFeed']);

        Route::post('/', [PostController::class, 'store']);
    });

    Route::get('/{id}',          [PostController::class, 'show'])->whereNumber('id');
    Route::get('/{id}/comments', [PostController::class, 'comments'])->whereNumber('id');

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        // Both handlers dereference $request->user(), so they cannot stay public.
        Route::post('/{id}/comments', [PostController::class, 'storeComment'])->whereNumber('id');
        Route::delete('/comments/{commentId}', [PostController::class, 'destroyComment'])->whereNumber('commentId');

        Route::patch('/{id}',  [PostController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [PostController::class, 'destroy'])->whereNumber('id');

        Route::post('/{id}/save',   [PostController::class, 'save'])->whereNumber('id');
        Route::delete('/{id}/save', [PostController::class, 'unsave'])->whereNumber('id');

        Route::patch('/{id}/archive',   [PostController::class, 'archive'])->whereNumber('id');
        Route::patch('/{id}/unarchive', [PostController::class, 'unarchive'])->whereNumber('id');

        Route::post('/{id}/hide',   [PostController::class, 'hideFromFeed'])->whereNumber('id');
        Route::delete('/{id}/hide', [PostController::class, 'unhideFromFeed'])->whereNumber('id');
    });
});
