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
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (!$query || strlen($query) < 2) {
            return response()->json(['message' => 'Query must be at least 2 characters'], 422);
        }

        $users = User::where('is_deleted', false)
            ->where(fn($q) => $q
                ->where('username', 'ILIKE', "%{$query}%")
            )
            ->select('id', 'username', 'avatar_url', 'bio')
            ->limit(20)
            ->get();

        return response()->json($users);
    }
}
