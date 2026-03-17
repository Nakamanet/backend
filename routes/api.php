<?php

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;

use App\Http\Controllers\Post\PostController;

const POST_WITH_ID = '/posts/{id}';

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::get(POST_WITH_ID, [PostController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);

        Route::post('/posts', [PostController::class, 'store']);
        Route::patch(POST_WITH_ID, [PostController::class, 'update']);
        Route::delete(POST_WITH_ID, [PostController::class, 'destroy']);
    });
});

