<?php

namespace App\Http\Controllers\Friendship;

use App\Http\Controllers\Controller;
use App\Models\Friendship\Friendship;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    // send a friend request
    public function send(Request $request)
    {
        $request->validate([
            'addressee_id' => 'required|integer|exists:Users,id',
        ]);

        $userId     = $request->user()->id;
        $addresseeId = $request->addressee_id;

        if ($userId === $addresseeId) {
            return response()->json(['message' => 'You cannot add yourself'], 422);
        }

        $existing = Friendship::where('requester_id', $userId)
            ->where('addressee_id', $addresseeId)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Request already sent'], 422);
        }

        $friendship = Friendship::create([
            'requester_id' => $userId,
            'addressee_id' => $addresseeId,
            'status'       => 'pending',
        ]);

        return response()->json($friendship, 201);
    }

    // accept a friend request
    public function accept(Request $request, $id)
    {
        $friendship = Friendship::where('id', $id)
            ->where('addressee_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'accepted']);

        return response()->json(['message' => 'Friend request accepted']);
    }

    // decline or cancel a friend request
    public function decline(Request $request, $id)
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('addressee_id', $userId);
            })
            ->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'Friend request declined']);
    }

    // block a user
    public function block(Request $request, $id)
    {
        $friendship = Friendship::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('requester_id', $request->user()->id)
                  ->orWhere('addressee_id', $request->user()->id);
            })
            ->firstOrFail();

        $friendship->update(['status' => 'blocked']);

        return response()->json(['message' => 'User blocked']);
    }

    // list my friends
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $friends = Friendship::with(['requester', 'addressee'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('addressee_id', $userId);
            })
            ->get();

        return response()->json($friends);
    }

    // list pending requests received
    public function pending(Request $request)
    {
        $pending = Friendship::with('requester')
            ->where('addressee_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json($pending);
    }
}
