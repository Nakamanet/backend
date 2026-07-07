<?php

use App\Http\Controllers\Upload\UploadController;

Route::middleware(['auth:api', 'user.active'])->prefix('upload')->group(function () {
    Route::post('/avatar',     [UploadController::class, 'avatar']);
    Route::post('/banner',     [UploadController::class, 'banner']);
    Route::post('/post-image', [UploadController::class, 'postImage']);
    Route::post('/emoji',      [UploadController::class, 'emoji']);
});
