<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function profile(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        $postsCount   = $target->posts()->whereNull('archived_at')->count();
        $friendsCount = Friendship::where('status', 'accepted')
            ->where(fn($q) => $q->where('requester_id', $target->id)->orWhere('addressee_id', $target->id))
            ->count();
        $libraryCount = $target->animeLibrary()->count() + $target->mangaLibrary()->count();

        // Always visible regardless of visibility setting
        $public = [
            'id'                 => $target->id,
            'username'           => $target->username,
            'avatar_url'         => $target->avatar_url,
            'banner_url'         => $target->banner_url,
            'bio'                => $target->bio,
            'localisation'       => $target->localisation,
            'profile_visibility' => $target->profile_visibility,
            'created_at'         => $target->created_at,
            'posts_count'        => $postsCount,
            'friends_count'      => $friendsCount,
            'library_count'      => $libraryCount,
        ];

        if ($target->profile_visibility === 'private') {
            return response()->json($public, 200);
        }

        if ($target->profile_visibility === 'friends_only') {
            // Guest users see only public data
            if (! $request->user()) {
                return response()->json($public, 200);
            }

            $isFriend = Friendship::where('status', 'accepted')
                ->where(function ($q) use ($request, $target) {
                    $q->where('requester_id', $request->user()->id)
                    ->where('addressee_id', $target->id);
                })
                ->orWhere(function ($q) use ($request, $target) {
                    $q->where('requester_id', $target->id)
                    ->where('addressee_id', $request->user()->id);
                })
                ->exists();

            if (! $isFriend && $request->user()->id !== $target->id) {
                return response()->json($public, 200);
            }
        }

        // public visibility OR confirmed friend OR viewing own profile
        return response()->json([
            ...$public,
            'role' => $target->role,
        ]);
    }
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

    public function updateVisibility(Request $request): JsonResponse
    {
        $request->validate([
            'profile_visibility' => 'required|in:public,friends_only,private',
        ]);

        $request->user()->update([
            'profile_visibility' => $request->profile_visibility,
        ]);

        return response()->json(['message' => 'Visibility updated']);
    }
}
