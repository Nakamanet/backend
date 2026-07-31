<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Friendship\Friendship;
use App\Models\Comment\Comment;

class PostController extends Controller
{
    /**
     * Normalize a boolean value to a Postgres-safe literal.
     *
     * The Neon/PgBouncer pooler forces PDO::ATTR_EMULATE_PREPARES, which inlines
     * bindings as PHP-typed literals. A PHP int/bool is inlined as 0/1 (an integer
     * literal Postgres refuses to cast into a boolean column), so any boolean written
     * or compared through the Query Builder must be passed as a 'true'/'false' string.
     */
    private function boolLiteral($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    /**
     * Attach the viewer-relative friendship status to each post author, so the
     * client can adapt its UI (hide the comment box, link to a restricted
     * profile, …) without one extra request per post.
     */
    private function attachAuthorFriendship($posts, ?int $viewerId): void
    {
        $posts = is_iterable($posts) ? $posts : [$posts];

        $authorIds = collect($posts)
            ->map(fn($post) => $post->relationLoaded('user') ? $post->user?->id : null)
            ->filter()
            ->all();

        $map = Friendship::statusMapFor($viewerId, $authorIds);

        $default = ['friendship_status' => 'none', 'friendship_id' => null];

        foreach ($posts as $post) {
            if (! $post->relationLoaded('user') || ! $post->user) {
                continue;
            }

            $status = $map[$post->user->id] ?? $default;

            $post->user->setAttribute('friendship_status', $status['friendship_status']);
            $post->user->setAttribute('friendship_id', $status['friendship_id']);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $userId    = $request->user()?->id;
        $blockedIds = Friendship::blockedUserIdsFor($userId);

        $query = Post::withCount([
                'likes',
                // Keep the count consistent with what GET /posts/{id}/comments returns.
                'comments' => fn($q) => $q->whereNotIn('user_id', $blockedIds),
            ])
            ->with('user')
            ->withViewerFlags($userId)
            ->visibleTo($userId)
            ->when($request->user_id,  fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_spoiler'),  fn($q) => $q->where('is_spoiler', $this->boolLiteral($request->is_spoiler)))
            ->when($request->has_images, fn($q) => $q->whereNotNull('image_urls'));

        // Exclude posts from users in a blocked relationship with the caller (both directions).
        if ($blockedIds) {
            $query->whereNotIn('user_id', $blockedIds);
        }

        if ($request->boolean('friends_only')) {
            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $friendIds = \App\Models\Friendship\Friendship::whereRaw("status = 'accepted'")
                ->where(function ($q) use ($userId) {
                    $q->where('requester_id', $userId)
                    ->orWhere('addressee_id', $userId);
                })
                ->get()
                ->map(fn ($f) => $f->requester_id === $userId ? $f->addressee_id : $f->requester_id)
                ->unique()
                ->values();

            $query->whereIn('user_id', $friendIds);
        }

        match ($request->sort) {
            'oldest'         => $query->oldest('created_at'),
            'most_liked'     => $query->orderByDesc('likes_count'),
            'most_commented' => $query->orderByDesc('comments_count'),
            default          => $query->latest('created_at'),
        };

        if ($userId) {
            $query->with(['likes' => fn($q) => $q->where('user_id', $userId)]);
        }

        $posts = $query->paginate(20);

        $this->attachAuthorFriendship($posts->items(), $userId);

        return response()->json($posts);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $viewerId = $request->user()?->id;

        $post = Post::with('user')
            ->withViewerFlags($viewerId)
            ->findOrFail($id);

        // A blocked relationship (either direction) hides the post entirely, so a
        // direct link / notification / search result cannot bypass the feed filter.
        if ($viewerId && Friendship::isBlockedBetween($viewerId, $post->user_id)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $post->setRelation('comments', $this->visibleComments($post, $viewerId)->with('user')->get());

        $this->attachAuthorFriendship($post, $viewerId);

        return response()->json($post);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('is_spoiler', $data)) {
            $data['is_spoiler'] = $this->boolLiteral($data['is_spoiler']);
        }

        $post = Post::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($post, 201);
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        if (array_key_exists('is_spoiler', $data)) {
            $data['is_spoiler'] = $this->boolLiteral($data['is_spoiler']);
        }

        $post->update($data);

        return response()->json($post);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted']);
    }

    /**
     * Comments of a post, minus those written by users blocked with the viewer.
     */
    private function visibleComments(Post $post, ?int $viewerId)
    {
        return $post->comments()
            ->whereNotIn('user_id', Friendship::blockedUserIdsFor($viewerId));
    }

    public function comments(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        $comments = $this->visibleComments($post, $request->user()?->id)
            ->with('user')
            ->paginate(20);

        return response()->json($comments);
    }

    public function userPosts(Request $request, int $id): JsonResponse
    {
        $viewerId = $request->user()?->id;

        if ($viewerId && Friendship::isBlockedBetween($viewerId, $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Archived posts are excluded here; they have their own tab (archivedOwnPosts).
        $query = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->withViewerFlags($viewerId)
            ->where('user_id', $id)
            ->whereNull('archived_at');

        $posts = $query->latest('created_at')->paginate(20);

        $this->attachAuthorFriendship($posts->items(), $viewerId);

        return response()->json($posts);
    }

    // ===== New endpoints =====

    public function userLikedPosts(Request $request, int $id): JsonResponse
    {
        $target = \App\Models\User::findOrFail($id);

        if (! $target->isVisibleTo($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $viewerId = $request->user()->id;

        if (Friendship::isBlockedBetween($viewerId, $target->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->withViewerFlags($viewerId)
            ->visibleTo($viewerId)
            ->whereNotIn('user_id', Friendship::blockedUserIdsFor($viewerId))
            ->whereHas('likes', fn($q) => $q->where('user_id', $target->id))
            ->latest('created_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function savedPosts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->withViewerFlags($userId)
            ->visibleTo($userId)
            // A post saved before the block must disappear too: the rule is
            // mutual invisibility, not "invisible from now on".
            ->whereNotIn('user_id', Friendship::blockedUserIdsFor($userId))
            ->whereHas('savedBy', fn($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate(20);

        $this->attachAuthorFriendship($posts->items(), $userId);

        return response()->json($posts);
    }

    public function archivedOwnPosts(Request $request): JsonResponse
    {
        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->withViewerFlags($request->user()->id)
            ->where('user_id', $request->user()->id)
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function archivedFromFeed(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->withViewerFlags($userId)
            ->whereNotIn('user_id', Friendship::blockedUserIdsFor($userId))
            ->whereHas('archivedBy', fn($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate(20);

        $this->attachAuthorFriendship($posts->items(), $userId);

        return response()->json($posts);
    }

    public function save(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        $request->user()->savedPosts()->syncWithoutDetaching([$post->id]);

        return response()->json(['message' => 'Saved', 'saved' => true], 201);
    }

    public function unsave(Request $request, int $id): JsonResponse
    {
        $request->user()->savedPosts()->detach($id);

        return response()->json(['message' => 'Unsaved', 'saved' => false]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->update(['archived_at' => now()]);

        return response()->json(['message' => 'Archived', 'archived' => true]);
    }

    public function unarchive(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->update(['archived_at' => null]);

        return response()->json(['message' => 'Unarchived', 'archived' => false]);
    }

    public function hideFromFeed(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id === $request->user()->id) {
            return response()->json(['message' => 'Cannot hide your own posts; use archive instead'], 422);
        }

        $request->user()->archivedPosts()->syncWithoutDetaching([$post->id]);

        return response()->json(['message' => 'Hidden from feed', 'hidden' => true], 201);
    }

    public function unhideFromFeed(Request $request, int $id): JsonResponse
    {
        $request->user()->archivedPosts()->detach($id);

        return response()->json(['message' => 'Restored to feed', 'hidden' => false]);
    }
    public function storeComment(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        // Blocking cuts the conversation both ways: neither the blocker nor the
        // blocked user can comment on the other's posts.
        if (Friendship::isBlockedBetween($request->user()->id, $post->user_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:Comments,id',
            'is_spoiler' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_spoiler', $validated)) {
            $validated['is_spoiler'] = $this->boolLiteral($validated['is_spoiler']);
        }

        // Same rule when replying to a comment rather than to the post itself.
        if (! empty($validated['parent_id'])) {
            $parentAuthorId = Comment::whereKey($validated['parent_id'])->value('user_id');

            if ($parentAuthorId && Friendship::isBlockedBetween($request->user()->id, $parentAuthorId)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $comment = Comment::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        $comment->load('user');

        return response()->json($comment, 201);
    }

    public function destroyComment(Request $request, int $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
