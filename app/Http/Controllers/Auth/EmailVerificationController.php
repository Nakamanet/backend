<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToFrontend('already-confirmed');
        }

        $user->markEmailAsVerified();

        return $this->redirectToFrontend('confirmed');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already confirmed.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Confirmation email sent.']);
    }

    private function redirectToFrontend(string $status)
    {
        $frontend = config('app.frontend_url');

        if ($frontend) {
            return redirect()->away($frontend . '/email/' . $status);
        }

        return response()->json(['status' => $status]);
    }
}
