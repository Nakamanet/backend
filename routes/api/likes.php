<?php

use App\Http\Controllers\Like\LikeController;

Route::middleware(['auth:api', 'user.active'])->prefix('likes')->group(function () {
    Route::post('/toggle', [LikeController::class, 'toggle']);
});
