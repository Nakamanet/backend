<?php

namespace App\Http\Controllers\Like;

use App\Http\Controllers\Controller;
use App\Models\Like\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'post_id'    => 'nullable|integer|exists:Posts,id',
            'comment_id' => 'nullable|integer|exists:Comments,id',
        ]);

        // must have at least one
        if (empty($validated['post_id']) && empty($validated['comment_id'])) {
            return response()->json(['message' => 'post_id or comment_id is required'], 422);
        }

        $userId    = $request->user()->id;
        $postId    = $validated['post_id'] ?? null;
        $commentId = $validated['comment_id'] ?? null;

        $existing = Like::where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('comment_id', $commentId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Unliked', 'liked' => false]);
        }

        Like::create([
            'user_id'    => $userId,
            'post_id'    => $postId,
            'comment_id' => $commentId,
        ]);

        return response()->json(['message' => 'Liked', 'liked' => true], 201);
    }
}
