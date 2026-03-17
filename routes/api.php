<?php

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;

use App\Http\Controllers\Post\PostController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);
        Route::put('/disable/{id}',[UserController::class, 'disableAccount']);

        Route::post('/posts', [PostController::class, 'store']);
        Route::patch('/posts/{id}', [PostController::class, 'update']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    });
});

