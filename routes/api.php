<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-logs', function () {
    return response()->file(storage_path('logs/laravel.log'));
});

require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/user.php';
require __DIR__ . '/api/post.php';
require __DIR__ . '/api/forum.php';
require __DIR__ . '/api/search.php';
require __DIR__ . '/api/notifications.php';
require __DIR__ . '/api/admin.php';
