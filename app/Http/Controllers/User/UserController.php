<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => 'sometimes|string|max:50|unique:Users,username,' . $user->id,
            'email' => 'sometimes|email|max:100|unique:Users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'birthdate' => 'sometimes|date',
            'localisation' => 'sometimes|string|max:100|nullable',
            'bio' => 'sometimes|string|max:500|nullable',
            'avatar_url' => 'sometimes|url|nullable',
            'banner_url' => 'sometimes|url|nullable',
            'theme_preference' => 'sometimes|string|in:light,dark,system',
        ]);

        if (isset($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }
}
