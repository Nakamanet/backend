<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->latest('created_at')->paginate(20);
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
}
