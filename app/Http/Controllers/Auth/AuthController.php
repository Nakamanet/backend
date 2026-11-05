<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SendRegistrationCodeRequest;
use App\Mail\RegistrationCodeMail;
use App\Services\Auth\RegistrationCodeService;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Signup step 1: email a one-time code to prove the address is real.
     */
    public function sendRegistrationCode(SendRegistrationCodeRequest $request, RegistrationCodeService $codes)
    {
        $email = $request->email;
        $code = $codes->issue($email);

        if ($code === null) {
            return response()->json([
                'message'     => 'A code was just sent. Please wait before asking for a new one.',
                'retry_after' => RegistrationCodeService::RESEND_COOLDOWN_SECONDS,
            ], 429);
        }

        try {
            Mail::to($email)->send(new RegistrationCodeMail($code));
        } catch (\Throwable $e) {
            report($e);
            $codes->forget($email);

            return response()->json([
                'message' => 'Unable to send the verification email right now. Please try again.',
            ], 503);
        }

        return response()->json([
            'message'     => 'Verification code sent.',
            'expires_in'  => RegistrationCodeService::TTL_MINUTES * 60,
            'retry_after' => RegistrationCodeService::RESEND_COOLDOWN_SECONDS,
        ], 202);
    }

    /**
     * Signup step 2: create the account once the emailed code checks out.
     */
    public function register(RegisterRequest $request, RegistrationCodeService $codes)
    {
        if (! $codes->verify($request->email, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        $user = User::create([
            'username'      => $request->username,
            'handle'        => $request->username, // handle = username at signup, frozen from here on
            'email'         => $request->email,
            'password_hash' => Hash::make($request->password),
            'birthdate'     => $request->birthdate,
            'localisation'  => $request->localisation,
            'role'          => 'user',
        ]);

        // The code proved the address, so the account starts verified.
        $user->markEmailAsVerified();
        $codes->forget($request->email);

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
        return response()->json(auth('api')->user()?->withPrivateFields());
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
            // The caller is this user, so their own private fields are theirs to read.
            'user'       => $user->withPrivateFields(),
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $status);
    }
}
