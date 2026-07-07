<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\ReplyTopicRequest;
use App\Http\Requests\Forum\StoreTopicRequest;
use App\Models\Forum\ForumReply;
use App\Models\Forum\ForumTopic;
use App\Models\Forum\ForumTopicView;
use App\Models\Forum\ForumUserPin;
use App\Models\Forum\ForumVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->when($request->search, fn($q) => $q->where(fn($sub) => $sub
                ->where('title', 'ILIKE', '%' . $request->search . '%')
                ->orWhere('content', 'ILIKE', '%' . $request->search . '%')
                ->orWhereHas('user', fn($u) => $u->where('username', 'ILIKE', '%' . $request->search . '%'))
            ))
            ->when($request->category,  fn($q) => $q->where('category', $request->category))
            ->when($request->user_id,   fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id,  fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id,  fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_pinned'),   fn($q) => $q->where('is_pinned',   filter_var($request->is_pinned,   FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('is_locked'),   fn($q) => $q->where('is_locked',   filter_var($request->is_locked,   FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('is_archived'), fn($q) => $q->where('is_archived', filter_var($request->is_archived, FILTER_VALIDATE_BOOLEAN)))
            ->when(!$request->has('is_archived'), fn($q) => $q->where('is_archived', false));

        match ($request->sort) {
            'oldest'       => $topics->oldest('created_at'),
            'most_replied' => $topics->orderByDesc('replies_count'),
            default        => $topics->orderByDesc('is_pinned')->latest('created_at'),
        };

        $paginator = $topics->paginate(20);

        $user = auth('api')->user();
        if ($user) {
            $topicIds   = $paginator->pluck('id')->toArray();
            $pinnedIds  = $topicIds ? ForumUserPin::where('user_id', $user->id)
                ->whereIn('topic_id', $topicIds)
                ->pluck('topic_id')
                ->toArray() : [];

            $paginator->getCollection()->transform(function ($topic) use ($pinnedIds) {
                $topic->user_has_pinned = in_array($topic->id, $pinnedIds);
                return $topic;
            });
        } else {
            $paginator->getCollection()->transform(function ($topic) {
                $topic->user_has_pinned = false;
                return $topic;
            });
        }

        return response()->json($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $topic = ForumTopic::with(['user', 'replies.user'])->findOrFail($id);

        $user = auth('api')->user();

        if ($topic->is_archived) {
            $canView = $user && (
                $topic->user_id === $user->id ||
                in_array($user->role, ['moderator', 'admin'])
            );
            if (!$canView) {
                return response()->json(['message' => 'Not Found'], 404);
            }
        }
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

            $topic->user_has_pinned = ForumUserPin::where([
                'user_id'  => $user->id,
                'topic_id' => $id,
            ])->exists();

            $replyIds      = $topic->replies->pluck('id')->toArray();
            $votedReplyIds = $replyIds ? ForumVote::where('user_id', $user->id)
                ->where('target_type', 'reply')
                ->whereIn('target_id', $replyIds)
                ->pluck('target_id')
                ->toArray() : [];

            foreach ($topic->replies as $reply) {
                $reply->user_has_voted = in_array($reply->id, $votedReplyIds);
            }
        } else {
            $topic->user_has_voted  = false;
            $topic->user_has_pinned = false;
            foreach ($topic->replies as $reply) {
                $reply->user_has_voted = false;
            }
        }

        return response()->json($topic);
    }

    public function pinTopic(Request $request, int $id): JsonResponse
    {
        ForumTopic::findOrFail($id);

        $pin = ForumUserPin::where([
            'user_id'  => $request->user()->id,
            'topic_id' => $id,
        ])->first();

        if ($pin) {
            $pin->delete();
            return response()->json(['user_has_pinned' => false]);
        }

        ForumUserPin::create(['user_id' => $request->user()->id, 'topic_id' => $id]);
        return response()->json(['user_has_pinned' => true]);
    }

    public function userPins(Request $request): JsonResponse
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->whereHas('userPins', fn($q) => $q->where('user_id', $request->user()->id))
            ->latest('created_at')
            ->paginate(20);

        $topics->getCollection()->transform(function ($topic) {
            $topic->user_has_pinned = true;
            return $topic;
        });

        return response()->json($topics);
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

        if ($topic->is_locked || $topic->is_archived) {
            return response()->json(['message' => 'Topic is locked'], 403);
        }

        $reply = ForumReply::create([
            ...$request->validated(),
            'topic_id' => $topic->id,
            'user_id'  => $request->user()->id,
        ]);

        return response()->json($reply, 201);
    }

    public function archiveTopic(Request $request, int $id): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user  = $request->user();

        $canArchive = $topic->user_id === $user->id
            || in_array($user->role, ['moderator', 'admin']);

        if (!$canArchive) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $topic->update(['is_archived' => !$topic->is_archived]);

        return response()->json(['is_archived' => $topic->is_archived]);
    }

    public function myArchivedTopics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->where('user_id', $userId)
            ->where('is_archived', true)
            ->latest('created_at')
            ->paginate(20);

        $topicIds  = $topics->pluck('id')->toArray();
        $pinnedIds = $topicIds ? ForumUserPin::where('user_id', $userId)
            ->whereIn('topic_id', $topicIds)
            ->pluck('topic_id')
            ->toArray() : [];

        $topics->getCollection()->transform(function ($topic) use ($pinnedIds) {
            $topic->user_has_pinned = in_array($topic->id, $pinnedIds);
            return $topic;
        });

        return response()->json($topics);
    }

    public function myVotedTopics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $votedIds = ForumVote::where('user_id', $userId)
            ->where('target_type', 'topic')
            ->pluck('target_id')
            ->toArray();

        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->whereIn('id', $votedIds)
            ->where('is_archived', false)
            ->latest('created_at')
            ->paginate(20);

        $topicIds  = $topics->pluck('id')->toArray();
        $pinnedIds = $topicIds ? ForumUserPin::where('user_id', $userId)
            ->whereIn('topic_id', $topicIds)
            ->pluck('topic_id')
            ->toArray() : [];

        $topics->getCollection()->transform(function ($topic) use ($pinnedIds, $votedIds) {
            $topic->user_has_pinned = in_array($topic->id, $pinnedIds);
            $topic->user_has_voted  = in_array($topic->id, $votedIds);
            return $topic;
        });

        return response()->json($topics);
    }

    public function userTopics(int $id): JsonResponse
    {
        $topics = ForumTopic::withCount('replies')
            ->with('user')
            ->where('user_id', $id)
            ->where('is_archived', false)
            ->latest('created_at')
            ->paginate(20);

        $user = auth('api')->user();
        if ($user) {
            $topicIds  = $topics->pluck('id')->toArray();
            $pinnedIds = $topicIds ? ForumUserPin::where('user_id', $user->id)
                ->whereIn('topic_id', $topicIds)
                ->pluck('topic_id')
                ->toArray() : [];

            $topics->getCollection()->transform(function ($topic) use ($pinnedIds) {
                $topic->user_has_pinned = in_array($topic->id, $pinnedIds);
                return $topic;
            });
        } else {
            $topics->getCollection()->transform(function ($topic) {
                $topic->user_has_pinned = false;
                return $topic;
            });
        }

        return response()->json($topics);
    }
}
