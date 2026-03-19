<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->anime_id, fn($q) => $q->where('related_anime_id', $request->anime_id))
            ->when($request->manga_id, fn($q) => $q->where('related_manga_id', $request->manga_id))
            ->when($request->has('is_spoiler'), fn($q) => $q->where('is_spoiler', filter_var($request->is_spoiler, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has_images, fn($q) => $q->whereNotNull('image_urls'));

        match($request->sort) {
            'oldest'        => $query->oldest('created_at'),
            'most_liked'    => $query->orderByDesc('likes_count'),
            'most_commented'=> $query->orderByDesc('comments_count'),
            default         => $query->latest('created_at'),
        };

        // is_liked for authenticated user
        if ($request->user()) {
            $userId = $request->user()->id;
            $query->with(['likes' => fn($q) => $q->where('user_id', $userId)]);
        }

        $posts = $query->paginate(20);

        return response()->json($posts);
    }

    public function show($id)
    {
        $post = Post::with(['user', 'comments.user'])->findOrFail($id);
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content'           => 'required|string|max:5000',
            'related_anime_id'  => 'nullable|integer',
            'related_manga_id'  => 'nullable|integer',
            'image_urls'        => 'nullable|array',
            'image_urls.*'      => 'url',
            'is_spoiler'        => 'boolean',
        ]);

        $post = Post::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($post, 201);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'content'    => 'sometimes|string|max:5000',
            'is_spoiler' => 'sometimes|boolean',
            'image_urls' => 'sometimes|array',
        ]);

        $post->update($validated);

        return response()->json($post);
    }

    public function destroy(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted']);
    }

    public function comments($id)
    {
        $post = Post::findOrFail($id);
        $comments = $post->comments()->with('user')->paginate(20);
        return response()->json($comments);
    }

    public function userPosts($id)
    {
        $posts = Post::withCount(['likes', 'comments'])
            ->with('user')
            ->where('user_id', $id)
            ->latest('created_at')
            ->paginate(20);

        return response()->json($posts);
    }
}
