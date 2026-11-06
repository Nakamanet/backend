<?php

require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/posts.php';
require __DIR__ . '/api/reports.php';
require __DIR__ . '/api/likes.php';
require __DIR__ . '/api/forum.php';
require __DIR__ . '/api/friendships.php';
require __DIR__ . '/api/upload.php';
require __DIR__ . '/api/library.php';
require __DIR__ . '/api/notifications.php';
require __DIR__ . '/api/admin.php';
require __DIR__ . '/api/translation.php';

Route::get('/debug-middleware', function () {
    return response()->file(app_path('Http/Middleware/EnsureUserIsAdmin.php'));
});

Route::post('/moderate', function (\Illuminate\Http\Request $request) {
    $text = $request->input('content') ?? $request->input('text') ?? '';
    if (trim($text) === '') {
        return response()->json(['flagged' => false]);
    }
    $mod = new \App\Services\ModerationService();
    $flagged = $mod->isFlagged($text);
    return response()->json(['flagged' => $flagged]);
});
