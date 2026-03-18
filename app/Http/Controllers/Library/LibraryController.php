<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Library\UserAnimeLibrary;
use App\Models\Library\UserMangaLibrary;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    // GET /library/anime
    public function animeIndex(Request $request)
    {
        $library = UserAnimeLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    // POST /library/anime
    public function animeStore(Request $request)
    {
        $validated = $request->validate([
            'anime_id'      => 'required|integer',
            'status'        => 'required|in:watching,completed,on_hold,dropped,plan_to_watch',
            'progress'      => 'nullable|integer|min:0',
            'rewatch_count' => 'nullable|integer|min:0',
            'score'         => 'nullable|integer|min:1|max:10',
            'is_private'    => 'boolean',
        ]);

        $entry = UserAnimeLibrary::updateOrCreate(
            ['user_id' => $request->user()->id, 'anime_id' => $validated['anime_id']],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json($entry, 201);
    }

    // DELETE /library/anime/{anime_id}
    public function animeDestroy(Request $request, $animeId)
    {
        UserAnimeLibrary::where('user_id', $request->user()->id)
            ->where('anime_id', $animeId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }

    // GET /library/manga
    public function mangaIndex(Request $request)
    {
        $library = UserMangaLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    // POST /library/manga
    public function mangaStore(Request $request)
    {
        $validated = $request->validate([
            'manga_id'     => 'required|integer',
            'status'       => 'required|in:watching,completed,on_hold,dropped,plan_to_watch',
            'progress'     => 'nullable|integer|min:0',
            'reread_count' => 'nullable|integer|min:0',
            'score'        => 'nullable|integer|min:1|max:10',
            'is_private'   => 'boolean',
        ]);

        $entry = UserMangaLibrary::updateOrCreate(
            ['user_id' => $request->user()->id, 'manga_id' => $validated['manga_id']],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json($entry, 201);
    }

    // DELETE /library/manga/{manga_id}
    public function mangaDestroy(Request $request, $mangaId)
    {
        UserMangaLibrary::where('user_id', $request->user()->id)
            ->where('manga_id', $mangaId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }
}
