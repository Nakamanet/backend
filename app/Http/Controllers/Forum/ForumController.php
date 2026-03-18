<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumReply;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    // list topics, optionally filter by category
    public function index(Request $request)
    {
        $topics = ForumTopic::with('user')
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->orderByDesc('is_pinned')
            ->latest('created_at')
            ->paginate(20);

        return response()->json($topics);
    }

    // single topic with its replies
    public function show($id)
    {
        $topic = ForumTopic::with(['user', 'replies.user'])->findOrFail($id);
        return response()->json($topic);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'content'           => 'required|string',
            'category'          => 'required|in:general,anime,manga,recommendations,spoilers',
            'related_anime_id'  => 'nullable|integer',
            'related_manga_id'  => 'nullable|integer',
        ]);

        $topic = ForumTopic::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($topic, 201);
    }

    public function destroy(Request $request, $id)
    {
        $topic = ForumTopic::findOrFail($id);

        if ($topic->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $topic->delete();
        return response()->json(['message' => 'Topic deleted']);
    }

    public function reply(Request $request, $id)
    {
        $topic = ForumTopic::findOrFail($id);

        if ($topic->is_locked) {
            return response()->json(['message' => 'Topic is locked'], 403);
        }

        $validated = $request->validate([
            'content'   => 'required|string',
            'parent_id' => 'nullable|integer|exists:Forum_Replies,id',
        ]);

        $reply = ForumReply::create([
            ...$validated,
            'topic_id' => $topic->id,
            'user_id'  => $request->user()->id,
        ]);

        return response()->json($reply, 201);
    }
}
