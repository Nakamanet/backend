<?php

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;

use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Like\LikeController;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);

    
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/likes/toggle', [LikeController::class, 'toggle']);

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);
        Route::put('/disable/{id}',[UserController::class, 'disableAccount']);

        Route::post('/posts', [PostController::class, 'store']);
        Route::patch('/posts/{id}', [PostController::class, 'update']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    });
});


use App\Http\Controllers\Forum\ForumController;

Route::prefix('forum')->group(function () {
    Route::get('/topics', [ForumController::class, 'index']);
    Route::get('/topics/{id}', [ForumController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/topics', [ForumController::class, 'store']);
        Route::delete('/topics/{id}', [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
    });
});