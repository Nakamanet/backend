<?php
// app/Http/Middleware/EnsureUserIsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }

        return $next($request);
    }
}
