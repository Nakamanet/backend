<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Like\LikeController;
use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Friendship\FriendshipController;
use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\Upload\UploadController;

use App\Http\Controllers\Auth\EmailVerificationController;

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Users
Route::middleware('auth:api')->prefix('users')->group(function () {
    Route::patch('/profile', [UserController::class, 'updateProfile']);
    Route::get('/{id}/profile', [UserController::class, 'getProfile']);
    Route::put('/disable/{id}', [UserController::class, 'disableAccount']);
    Route::delete('/{id}', [UserController::class, 'deleteAccount']);

    Route::get('/{id}/posts', [PostController::class, 'userPosts']);
    Route::get('/{id}/forum-topics', [ForumController::class, 'userTopics']);
});

// Posts
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{id}', [PostController::class, 'show']);
    Route::get('/{id}/comments', [PostController::class, 'comments']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::patch('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
    });
});

// Likes
Route::middleware('auth:api')->prefix('likes')->group(function () {
    Route::post('/toggle', [LikeController::class, 'toggle']);
});

// Forum
Route::prefix('forum')->group(function () {
    Route::get('/topics', [ForumController::class, 'index']);
    Route::get('/topics/{id}', [ForumController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/topics', [ForumController::class, 'store']);
        Route::delete('/topics/{id}', [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
        Route::post('/topics/{id}/vote', [ForumController::class, 'voteOnTopic']);
        Route::post('/replies/{id}/vote', [ForumController::class, 'voteOnReply']);
    });
});

// Friendships
Route::middleware('auth:api')->prefix('friends')->group(function () {
    Route::get('/', [FriendshipController::class, 'index']);
    Route::get('/pending', [FriendshipController::class, 'pending']);
    Route::post('/send', [FriendshipController::class, 'send']);
    Route::patch('/{id}/accept', [FriendshipController::class, 'accept']);
    Route::delete('/{id}/decline', [FriendshipController::class, 'decline']);
    Route::patch('/{id}/block', [FriendshipController::class, 'block']);
});

// Upload
Route::middleware('auth:api')->prefix('upload')->group(function () {
    Route::post('/avatar', [UploadController::class, 'avatar']);
    Route::post('/banner', [UploadController::class, 'banner']);
});

// Library
Route::middleware('auth:api')->prefix('library')->group(function () {
    Route::get('/anime', [LibraryController::class, 'animeIndex']);
    Route::post('/anime', [LibraryController::class, 'animeStore']);
    Route::delete('/anime/{anime_id}', [LibraryController::class, 'animeDestroy']);

    Route::get('/manga', [LibraryController::class, 'mangaIndex']);
    Route::post('/manga', [LibraryController::class, 'mangaStore']);
    Route::delete('/manga/{manga_id}', [LibraryController::class, 'mangaDestroy']);
});
