<?php

use App\Http\Controllers\Admin\AdminUserController;

Route::middleware(['auth:api', 'user.active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/cleanup-bots', [AdminUserController::class, 'cleanupBots']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::patch('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    Route::delete('/users/{id}/force', [AdminUserController::class, 'forceDestroy']);
    Route::patch('/users/{id}/restore', [AdminUserController::class, 'restore']);
});
