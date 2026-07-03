<?php

namespace App\Http\Controllers\Friendship;

use App\Http\Controllers\Controller;
use App\Http\Requests\Friendship\SendFriendRequest;
use App\Models\Friendship\Friendship;
use App\Models\Notification\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $friendship = DB::transaction(function () use ($userId, $addresseeId) {
            $friendship = Friendship::create([
                'requester_id' => $userId,
                'addressee_id' => $addresseeId,
                'status'       => 'pending',
            ]);

            Notification::create([
                'recipient_id' => $addresseeId,
                'sender_id'    => $userId,
                'type'         => 'friend_request',
                // Raw boolean literal: PDO emulated prepares (Neon pooler) would
                // otherwise inline PHP false as integer 0, rejected by the
                // boolean "is_read" column.
                'is_read'      => DB::raw('false'),
                'payload'      => ['friendship_id' => $friendship->id],
            ]);

            return $friendship;
        });

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
    public function sent(Request $request): JsonResponse
    {
        $sent = Friendship::with('requester', 'addressee')
            ->where('requester_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json($sent);
    }

    public function blocked(Request $request): JsonResponse
    {
        $blocked = Friendship::with('requester', 'addressee')
            ->where('requester_id', $request->user()->id)
            ->where('status', 'blocked')
            ->get();

        return response()->json($blocked);
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
