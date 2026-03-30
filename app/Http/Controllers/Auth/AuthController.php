<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * Register a new user and return a JWT token.
     */
    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50|unique:Users,username',
            'email'     => 'required|email|max:100|unique:Users,email',
            'password'  => 'required|string|min:8',
            'birthdate' => 'required|date',
        ]);

        $user = User::create([
            'username'      => $request->username,
            'email'         => $request->email,
            'password_hash' => Hash::make($request->password),
            'birthdate'     => $request->birthdate,
            'localisation'  => $request->localisation,
            'role'          => 'user',
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    /**
     * Login and return a JWT token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Manually log in via JWT (needed because password field is non-standard)
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    /**
     * Logout by invalidating the JWT token.
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Return the authenticated user.
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Refresh a JWT token.
     */
    public function refresh()
    {
        $token = auth('api')->refresh();

        return $this->respondWithToken($token, auth('api')->user());
    }

    /**
     * Shared token response format.
     */
    protected function respondWithToken(string $token, User $user, int $status = 200)
    {
        return response()->json([
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $status);
    }
}
