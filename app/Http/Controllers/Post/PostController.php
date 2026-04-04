<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post\Post;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['user_id', 'anime_id', 'manga_id', 'is_spoiler', 'has_images', 'sort', 'page']);
        $key    = CacheService::posts($params);

        $posts = CacheService::remember($key, CacheService::TTL_SHORT, function () use ($request) {
            $query = Post::withCount(['likes', 'comments'])
                ->with('user')
                ->when($request->user_id,  fn($q) => $q->where('user_id', $request->user_id))
                ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
                ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
                ->when($request->has('is_spoiler'), fn($q) => $q->where('is_spoiler', filter_var($request->is_spoiler, FILTER_VALIDATE_BOOLEAN)))
                ->when($request->has_images, fn($q) => $q->whereNotNull('image_urls'));

            match ($request->sort) {
                'oldest'         => $query->oldest('created_at'),
                'most_liked'     => $query->orderByDesc('likes_count'),
                'most_commented' => $query->orderByDesc('comments_count'),
                default          => $query->latest('created_at'),
            };

            if ($request->user()) {
                $userId = $request->user()->id;
                $query->with(['likes' => fn($q) => $q->where('user_id', $userId)]);
            }

            return $query->paginate(20);
        });

        return response()->json($posts);
    }

    public function show(int $id): JsonResponse
    {
        $post = CacheService::remember(
            CacheService::post($id),
            CacheService::TTL_MEDIUM,
            fn() => Post::with(['user', 'comments.user'])->findOrFail($id)
        );

        return response()->json($post);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = Post::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        CacheService::forget(CacheService::userPosts($request->user()->id));

        return response()->json($post, 201);
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->update($request->validated());

        CacheService::forget(CacheService::post($id));

        return response()->json($post);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->delete();

        CacheService::forget([
            CacheService::post($id),
            CacheService::userPosts($request->user()->id),
        ]);

        return response()->json(['message' => 'Post deleted']);
    }

    public function comments(int $id): JsonResponse
    {
        $page = request()->get('page', 1);

        $comments = CacheService::remember(
            CacheService::postComments($id, $page),
            CacheService::TTL_SHORT,
            function () use ($id) {
                $post = Post::findOrFail($id);
                return $post->comments()->with('user')->paginate(20);
            }
        );

        return response()->json($comments);
    }

    public function userPosts(int $id): JsonResponse
    {
        $page = request()->get('page', 1);

        $posts = CacheService::remember(
            CacheService::userPosts($id, $page),
            CacheService::TTL_MEDIUM,
            fn() => Post::withCount(['likes', 'comments'])
                ->with('user')
                ->where('user_id', $id)
                ->latest('created_at')
                ->paginate(20)
        );

        return response()->json($posts);
    }
}
