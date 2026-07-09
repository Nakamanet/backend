<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // $request->validated() gives only the validated fields
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

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

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
