<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Like\LikeController;
use App\Http\Controllers\Forum\ForumController;

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Users
Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::patch('/profile', [UserController::class, 'updateProfile']);
    Route::put('/disable/{id}', [UserController::class, 'disableAccount']);
});

// Posts
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{id}', [PostController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::patch('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
    });
});

// Likes
Route::middleware('auth:sanctum')->prefix('likes')->group(function () {
    Route::post('/toggle', [LikeController::class, 'toggle']);
});

// Forum
Route::prefix('forum')->group(function () {
    Route::get('/topics', [ForumController::class, 'index']);
    Route::get('/topics/{id}', [ForumController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/topics', [ForumController::class, 'store']);
        Route::delete('/topics/{id}', [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
    });
});
