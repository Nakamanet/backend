<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\ReplyTopicRequest;
use App\Http\Requests\Forum\StoreTopicRequest;
use App\Models\Forum\ForumReply;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumTopicView;
use App\Models\Forum\ForumVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->when($request->search,    fn($q) => $q->where(fn($sub) => $sub
                ->where('title', 'ILIKE', '%' . $request->search . '%')
                ->orWhere('content', 'ILIKE', '%' . $request->search . '%')
                ->orWhereHas('user', fn($u) => $u->where('username', 'ILIKE', '%' . $request->search . '%'))
            ))
            ->when($request->category,  fn($q) => $q->where('category', $request->category))
            ->when($request->user_id,   fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id,  fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id,  fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_pinned'), fn($q) => $q->where('is_pinned', filter_var($request->is_pinned, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('is_locked'), fn($q) => $q->where('is_locked', filter_var($request->is_locked, FILTER_VALIDATE_BOOLEAN)));

        match ($request->sort) {
            'oldest'       => $topics->oldest('created_at'),
            'most_replied' => $topics->orderByDesc('replies_count'),
            default        => $topics->orderByDesc('is_pinned')->latest('created_at'),
        };

        return response()->json($topics->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $topic = ForumTopic::with(['user', 'replies.user'])->findOrFail($id);

        $user = auth('api')->user();
        if ($user) {
            $view = ForumTopicView::firstOrCreate([
                'topic_id' => $id,
                'user_id'  => $user->id,
            ]);
            if ($view->wasRecentlyCreated) {
                $topic->increment('views_count');
                $topic->views_count++;
            }

            $topic->user_has_voted = ForumVote::where([
                'user_id'     => $user->id,
                'target_type' => 'topic',
                'target_id'   => $id,
            ])->exists();

            $replyIds = $topic->replies->pluck('id')->toArray();
            $votedReplyIds = $replyIds ? ForumVote::where('user_id', $user->id)
                ->where('target_type', 'reply')
                ->whereIn('target_id', $replyIds)
                ->pluck('target_id')
                ->toArray() : [];

            foreach ($topic->replies as $reply) {
                $reply->user_has_voted = in_array($reply->id, $votedReplyIds);
            }
        } else {
            $topic->user_has_voted = false;
            foreach ($topic->replies as $reply) {
                $reply->user_has_voted = false;
            }
        }

        return response()->json($topic);
    }

    public function voteOnTopic(Request $request, int $id): JsonResponse
    {
        return $this->toggleVote('topic', $id, ForumTopic::findOrFail($id), $request->user()->id);
    }

    public function voteOnReply(Request $request, int $id): JsonResponse
    {
        return $this->toggleVote('reply', $id, ForumReply::findOrFail($id), $request->user()->id);
    }

    private function toggleVote(string $type, int $targetId, $model, int $userId): JsonResponse
    {
        $vote = ForumVote::where([
            'user_id'     => $userId,
            'target_type' => $type,
            'target_id'   => $targetId,
        ])->first();

        if ($vote) {
            $vote->delete();
            $model->decrement('votes_count');
            $model->refresh();
            return response()->json(['votes_count' => $model->votes_count, 'user_has_voted' => false]);
        }

        ForumVote::create(['user_id' => $userId, 'target_type' => $type, 'target_id' => $targetId]);
        $model->increment('votes_count');
        $model->refresh();
        return response()->json(['votes_count' => $model->votes_count, 'user_has_voted' => true]);
    }

    public function store(StoreTopicRequest $request): JsonResponse
    {
        $topic = ForumTopic::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json($topic, 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);

        if ($topic->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $topic->delete();

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
