<?php

use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\User\UserController;

Route::middleware(['auth:api', 'throttle:api'])->prefix('users')->group(function () {
    Route::patch('/profile',         [UserController::class, 'updateProfile']);
    Route::put('/disable/{id}',      [UserController::class, 'disableAccount']);
    Route::get('/{id}/posts',        [PostController::class, 'userPosts']);
    Route::get('/{id}/forum-topics', [ForumController::class, 'userTopics']);
});
