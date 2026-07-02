<?php

namespace App\Http\Controllers\Friendship;

use App\Http\Controllers\Controller;
use App\Http\Requests\Friendship\SendFriendRequest;
use App\Models\Friendship\Friendship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function send(SendFriendRequest $request): JsonResponse
    {
        $userId      = $request->user()->id;
        $addresseeId = $request->addressee_id;

        if ($userId === $addresseeId) {
            return response()->json(['message' => 'You cannot add yourself'], 422);
        }

        $existing = Friendship::where(fn($q) => $q->where('requester_id', $userId)->where('addressee_id', $addresseeId))
            ->orWhere(fn($q) => $q->where('requester_id', $addresseeId)->where('addressee_id', $userId))
            ->first();

        if ($existing) {
            return response()->json(['message' => 'A relationship already exists with this user'], 422);
        }

        $friendship = Friendship::create([
            'requester_id' => $userId,
            'addressee_id' => $addresseeId,
            'status'       => 'pending',
        ]);

        return response()->json($friendship, 201);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $friendship = Friendship::where('id', $id)
            ->where('addressee_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'accepted']);

        return response()->json(['message' => 'Friend request accepted']);
    }

    public function decline(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where('id', $id)
            ->where(fn($q) => $q->where('requester_id', $userId)->orWhere('addressee_id', $userId))
            ->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'Friend request declined']);
    }

    public function block(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:Users,id',
        ]);

        $userId   = $request->user()->id;
        $targetId = $request->user_id;

        if ($userId === $targetId) {
            return response()->json(['message' => 'You cannot block yourself'], 422);
        }

        $friendship = Friendship::where(fn($q) => $q->where('requester_id', $userId)->where('addressee_id', $targetId))
            ->orWhere(fn($q) => $q->where('requester_id', $targetId)->where('addressee_id', $userId))
            ->first();

        if ($friendship) {
            $friendship->update([
                'requester_id' => $userId,
                'addressee_id' => $targetId,
                'status'       => 'blocked',
            ]);
        } else {
            $friendship = Friendship::create([
                'requester_id' => $userId,
                'addressee_id' => $targetId,
                'status'       => 'blocked',
            ]);
        }

        return response()->json(['message' => 'User blocked', 'friendship_id' => $friendship->id]);
    }

    public function unblock(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where('id', $id)
            ->where('requester_id', $userId)
            ->where('status', 'blocked')
            ->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'User unblocked']);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $friends = Friendship::with(['requester', 'addressee'])
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('requester_id', $userId)->orWhere('addressee_id', $userId))
            ->get();

        return response()->json($friends);
    }

    public function pending(Request $request): JsonResponse
    {
        $pending = Friendship::with('requester')
            ->where('addressee_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json($pending);
    }
    public function remove(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where('id', $id)
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('requester_id', $userId)->orWhere('addressee_id', $userId))
            ->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'Friend removed']);
    }
}
