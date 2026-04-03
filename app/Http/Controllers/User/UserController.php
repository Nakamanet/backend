<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh(),
        ]);
    }

    public function disableAccount(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->update(['is_deleted' => true]);

        return response()->json(['message' => 'Account disabled']);
    }
}
