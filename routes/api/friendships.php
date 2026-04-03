<?php

use App\Http\Controllers\Friendship\FriendshipController;

Route::middleware(['auth:api', 'throttle:friendships'])->prefix('friends')->group(function () {
    Route::get('/',               [FriendshipController::class, 'index']);
    Route::get('/pending',        [FriendshipController::class, 'pending']);
    Route::post('/send',          [FriendshipController::class, 'send']);
    Route::patch('/{id}/accept',  [FriendshipController::class, 'accept']);
    Route::delete('/{id}/decline',[FriendshipController::class, 'decline']);
    Route::patch('/{id}/block',   [FriendshipController::class, 'block']);
});
