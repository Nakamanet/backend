<?php

namespace App\Http\Controllers\Like;

use App\Http\Controllers\Controller;
use App\Http\Requests\Like\ToggleLikeRequest;
use App\Models\Like\Like;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function toggle(ToggleLikeRequest $request): JsonResponse
    {
        $userId    = $request->user()->id;
        $postId    = $request->post_id ?? null;
        $commentId = $request->comment_id ?? null;

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
