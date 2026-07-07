<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Friendship\Friendship;

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

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $query = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->visibleTo($userId)
            ->when($request->user_id,  fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_spoiler'),  fn($q) => $q->where('is_spoiler', $this->boolLiteral($request->is_spoiler)))
            ->when($request->has_images, fn($q) => $q->whereNotNull('image_urls'));

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

        return response()->json($query->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $post = Post::with(['user', 'comments.user'])->findOrFail($id);

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

    public function comments(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        return response()->json($post->comments()->with('user')->paginate(20));
    }

    public function userPosts(Request $request, int $id): JsonResponse
    {
        $isOwnProfile = $request->user()?->id === $id;

        $query = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->where('user_id', $id);

        // Only show archived posts on the owner's own profile view
        if (!$isOwnProfile) {
            $query->whereNull('archived_at');
        }

        return response()->json($query->latest('created_at')->paginate(20));
    }

    // ===== New endpoints =====

    public function likedPosts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->visibleTo($userId)
            ->whereHas('likes', fn($q) => $q->where('user_id', $userId)->where('is_liked', true))
            ->latest('created_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function savedPosts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->visibleTo($userId)
            ->whereHas('savedBy', fn($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function archivedOwnPosts(Request $request): JsonResponse
    {
        $posts = Post::withCount(['likes', 'comments'])
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
            ->whereHas('archivedBy', fn($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate(20);

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
}
