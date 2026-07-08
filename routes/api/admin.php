
<?php

use App\Http\Controllers\Admin\AdminUserController;

Route::middleware(['auth:api', 'user.active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::patch('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    Route::patch('/users/{id}/restore', [AdminUserController::class, 'restore']);
});
