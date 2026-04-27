<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Like\LikeController;
use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Friendship\FriendshipController;
use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\Notification\NotificationController;

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Users
Route::middleware(['auth:api', 'user.active'])->prefix('users')->group(function () {
    Route::get('/search', [UserController::class, 'search']);
    Route::patch('/profile', [UserController::class, 'updateProfile']);
    Route::put('/disable/{id}', [UserController::class, 'disableAccount']);
    Route::get('/{id}/posts', [PostController::class, 'userPosts']);
    Route::get('/{id}/forum-topics', [ForumController::class, 'userTopics']);
});

// Posts
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        // Personal collections — must come BEFORE /{id} to avoid route collision
        Route::get('/me/liked',              [PostController::class, 'likedPosts']);
        Route::get('/me/saved',              [PostController::class, 'savedPosts']);
        Route::get('/me/archived',           [PostController::class, 'archivedOwnPosts']);
        Route::get('/me/archived-from-feed', [PostController::class, 'archivedFromFeed']);

        Route::post('/',           [PostController::class, 'store']);
    });

    Route::get('/{id}',          [PostController::class, 'show'])->whereNumber('id');
    Route::get('/{id}/comments', [PostController::class, 'comments'])->whereNumber('id');

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::patch('/{id}',  [PostController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [PostController::class, 'destroy'])->whereNumber('id');

        Route::post('/{id}/save',     [PostController::class, 'save'])->whereNumber('id');
        Route::delete('/{id}/save',   [PostController::class, 'unsave'])->whereNumber('id');

        Route::patch('/{id}/archive',   [PostController::class, 'archive'])->whereNumber('id');
        Route::patch('/{id}/unarchive', [PostController::class, 'unarchive'])->whereNumber('id');

        Route::post('/{id}/hide',   [PostController::class, 'hideFromFeed'])->whereNumber('id');
        Route::delete('/{id}/hide', [PostController::class, 'unhideFromFeed'])->whereNumber('id');
    });
});

// Likes
Route::middleware(['auth:api', 'user.active'])->prefix('likes')->group(function () {
    Route::post('/toggle', [LikeController::class, 'toggle']);
});

// Forum
Route::prefix('forum')->group(function () {
    Route::get('/topics', [ForumController::class, 'index']);
    Route::get('/topics/{id}', [ForumController::class, 'show']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::post('/topics', [ForumController::class, 'store']);
        Route::delete('/topics/{id}', [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
        Route::post('/topics/{id}/vote', [ForumController::class, 'voteOnTopic']);
        Route::post('/replies/{id}/vote', [ForumController::class, 'voteOnReply']);
    });
});

// Friendships
Route::middleware(['auth:api', 'user.active'])->prefix('friends')->group(function () {
    Route::get('/', [FriendshipController::class, 'index']);
    Route::get('/pending', [FriendshipController::class, 'pending']);
    Route::post('/send', [FriendshipController::class, 'send']);
    Route::patch('/{id}/accept', [FriendshipController::class, 'accept']);
    Route::delete('/{id}/decline', [FriendshipController::class, 'decline']);
    Route::delete('/{id}/remove', [FriendshipController::class, 'remove']);
    Route::patch('/{id}/block', [FriendshipController::class, 'block']);
});

// Library
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

// Notifications
Route::middleware(['auth:api', 'user.active'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
});
