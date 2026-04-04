<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\ReplyTopicRequest;
use App\Http\Requests\Forum\StoreTopicRequest;
use App\Models\Forum\ForumReply;
use App\Models\Forum\ForumTopic;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['category', 'user_id', 'anime_id', 'manga_id', 'is_pinned', 'is_locked', 'sort', 'page']);
        $key    = CacheService::forumTopics($params);

        $topics = CacheService::remember($key, CacheService::TTL_SHORT, function () use ($request) {
            $query = ForumTopic::withCount('replies')
                ->with('user')
                ->when($request->category,  fn($q) => $q->where('category', $request->category))
                ->when($request->user_id,   fn($q) => $q->where('user_id', $request->user_id))
                ->when($request->anime_id,  fn($q) => $q->where('related_anime_id', $request->anime_id))
                ->when($request->manga_id,  fn($q) => $q->where('related_manga_id', $request->manga_id))
                ->when($request->has('is_pinned'), fn($q) => $q->where('is_pinned', filter_var($request->is_pinned, FILTER_VALIDATE_BOOLEAN)))
                ->when($request->has('is_locked'), fn($q) => $q->where('is_locked', filter_var($request->is_locked, FILTER_VALIDATE_BOOLEAN)));

            match ($request->sort) {
                'oldest'       => $query->oldest('created_at'),
                'most_replied' => $query->orderByDesc('replies_count'),
                default        => $query->orderByDesc('is_pinned')->latest('created_at'),
            };

            return $query->paginate(20);
        });

        return response()->json($topics);
    }

    public function show(int $id): JsonResponse
    {
        $topic = CacheService::remember(
            CacheService::forumTopic($id),
            CacheService::TTL_MEDIUM,
            fn() => ForumTopic::with(['user', 'replies.user'])->findOrFail($id)
        );

        return response()->json($topic);
    }

    public function store(StoreTopicRequest $request): JsonResponse
    {
        $topic = ForumTopic::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        // New topic → list caches are stale
        Cache::tags('forum:topics')->flush(); // if using Redis tags
        // Or without tags:
        // CacheService::forget(CacheService::forumTopics());

        return response()->json($topic, 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);

        if ($topic->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $topic->delete();

        CacheService::forget([
            CacheService::forumTopic($id),
        ]);

        return response()->json(['message' => 'Topic deleted']);
    }

    public function reply(ReplyTopicRequest $request, int $id): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);

        if ($topic->is_locked) {
            return response()->json(['message' => 'Topic is locked'], 403);
        }

        $reply = ForumReply::create([
            ...$request->validated(),
            'topic_id' => $topic->id,
            'user_id'  => $request->user()->id,
        ]);

        // Single topic cache now has wrong reply count
        CacheService::forget(CacheService::forumTopic($id));

        return response()->json($reply, 201);
    }

    public function userTopics(int $id): JsonResponse
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->where('user_id', $id)
            ->latest('created_at')
            ->paginate(20);

        return response()->json($topics);
    }
}
