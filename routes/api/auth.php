<?php

use App\Http\Controllers\Auth\AuthController;

Route::prefix('auth')->group(function () {
    // Signup: step 1 emails a code (Turnstile-guarded), step 2 creates the account.
    Route::post('/register/send-code', [AuthController::class, 'sendRegistrationCode'])
        ->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
