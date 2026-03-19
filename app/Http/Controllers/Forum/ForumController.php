<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumReply;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_pinned'), fn($q) => $q->where('is_pinned', filter_var($request->is_pinned, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('is_locked'), fn($q) => $q->where('is_locked', filter_var($request->is_locked, FILTER_VALIDATE_BOOLEAN)));

        match($request->sort) {
            'oldest'       => $topics->oldest('created_at'),
            'most_replied' => $topics->orderByDesc('replies_count'),
            default        => $topics->orderByDesc('is_pinned')->latest('created_at'),
        };

        return response()->json($topics->paginate(20));
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
    public function userTopics($id)
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->where('user_id', $id)
            ->latest('created_at')
            ->paginate(20);

        return response()->json($topics);
    }
}
