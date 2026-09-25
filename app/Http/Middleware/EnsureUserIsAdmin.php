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

        if (app/Models/User.phpuser || app/Models/User.phpuser->is_admin) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }

        return $next($request);
    }
}
