<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\AnimeLibraryRequest;
use App\Http\Requests\Library\MangaLibraryRequest;
use App\Models\Library\UserAnimeLibrary;
use App\Models\Library\UserMangaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function animeIndex(Request $request): JsonResponse
    {
        $library = UserAnimeLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    public function animeStore(AnimeLibraryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $entry = UserAnimeLibrary::updateOrCreate(
            ['user_id' => $request->user()->id, 'anime_id' => $validated['anime_id']],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json($entry, 201);
    }

    public function animeDestroy(Request $request, int $animeId): JsonResponse
    {
        UserAnimeLibrary::where('user_id', $request->user()->id)
            ->where('anime_id', $animeId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }

    public function mangaIndex(Request $request): JsonResponse
    {
        $library = UserMangaLibrary::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return response()->json($library);
    }

    public function mangaStore(MangaLibraryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $entry = UserMangaLibrary::updateOrCreate(
            ['user_id' => $request->user()->id, 'manga_id' => $validated['manga_id']],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json($entry, 201);
    }

    public function mangaDestroy(Request $request, int $mangaId): JsonResponse
    {
        UserMangaLibrary::where('user_id', $request->user()->id)
            ->where('manga_id', $mangaId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Removed from library']);
    }
}
