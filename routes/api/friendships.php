<?php

use App\Http\Controllers\Friendship\FriendshipController;

Route::middleware(['auth:api', 'user.active'])->prefix('friends')->group(function () {
    Route::get('/pending', [FriendshipController::class, 'pending']);
    Route::get('/sent', [FriendshipController::class, 'sent']);
    Route::get('/blocked', [FriendshipController::class, 'blocked']);
    Route::post('/send', [FriendshipController::class, 'send']);
    Route::patch('/{id}/accept', [FriendshipController::class, 'accept']);
    Route::delete('/{id}/decline', [FriendshipController::class, 'decline']);
    Route::delete('/{id}/remove', [FriendshipController::class, 'remove']);
    Route::post('/block', [FriendshipController::class, 'block']);
    Route::delete('/{id}/unblock', [FriendshipController::class, 'unblock']);
});
