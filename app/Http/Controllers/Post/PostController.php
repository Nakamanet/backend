<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $query = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->visibleTo($userId)
            ->when($request->user_id,  fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_spoiler'),  fn($q) => $q->where('is_spoiler', filter_var($request->is_spoiler, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has_images, fn($q) => $q->whereNotNull('image_urls'));

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
        $post = Post::create([
            ...$request->validated(),
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

        $post->update($request->validated());

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
