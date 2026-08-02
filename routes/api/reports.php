<?php

use App\Http\Controllers\Report\ReportController;

Route::middleware(['auth:api', 'user.active'])->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
});

Route::middleware(['auth:api', 'user.active', 'admin'])->prefix('admin')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports/dismiss', [ReportController::class, 'dismiss']);
    Route::post('/reports/action', [ReportController::class, 'takeAction']);
});
