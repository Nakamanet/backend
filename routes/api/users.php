<?php

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Friendship\FriendshipController;

Route::prefix('users')->group(function () {
    Route::get('/{id}/profile', [UserController::class, 'profile']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::get('/search', [UserController::class, 'search']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);
        Route::patch('/profile/visibility', [UserController::class, 'updateVisibility']);
        Route::put('/disable/{id}', [UserController::class, 'disableAccount']);
        Route::delete('/{id}', [UserController::class, 'deleteAccount']);
        Route::get('/{id}/posts', [PostController::class, 'userPosts']);
        Route::get('/{id}/liked-posts', [PostController::class, 'userLikedPosts']);
        Route::get('/{id}/friends', [FriendshipController::class, 'userFriends']);
        Route::get('/{id}/forum-topics', [ForumController::class, 'userTopics']);
    });
});
