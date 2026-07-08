<?php

use App\Http\Controllers\Forum\ForumController;

Route::prefix('forum')->group(function () {
    Route::get('/topics', [ForumController::class, 'index']);
    Route::get('/topics/{id}', [ForumController::class, 'show']);

    Route::middleware(['auth:api', 'user.active'])->group(function () {
        Route::get('/my-pins', [ForumController::class, 'userPins']);
        Route::get('/my-archived', [ForumController::class, 'myArchivedTopics']);
        Route::get('/my-voted', [ForumController::class, 'myVotedTopics']);
        Route::post('/topics', [ForumController::class, 'store']);
        Route::delete('/topics/{id}', [ForumController::class, 'destroy']);
        Route::post('/topics/{id}/reply', [ForumController::class, 'reply']);
        Route::post('/topics/{id}/vote', [ForumController::class, 'voteOnTopic']);
        Route::post('/topics/{id}/pin', [ForumController::class, 'pinTopic']);
        Route::post('/topics/{id}/archive', [ForumController::class, 'archiveTopic']);
        Route::post('/replies/{id}/vote', [ForumController::class, 'voteOnReply']);
    });
});
